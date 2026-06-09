<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('ZibDBResult')) {
    class ZibDBResult
    {
        protected $data = array();

        public function __construct($data = array())
        {
            $this->data = $data;
        }

        public function toArray()
        {
            return $this->data;
        }

        public function toArrayMap($callback = null)
        {
            if (!$callback || !is_callable($callback)) {
                return $this->data;
            }

            return array_map($callback, $this->data);
        }
    }
}

if (!class_exists('ZibDBQuery')) {
    class ZibDBQuery
    {
        protected $table = '';
        protected $fields = '*';
        protected $where = array();
        protected $where_values = array();
        protected $group = '';
        protected $order = '';
        protected $limit = '';
        protected $distinct = false;
        protected $meta_table = '';
        protected $joins = array();
        protected $or_conditions = array();

        //8.8 业务代码（如 zibpay/class/card-pass.php:108）会用 $db->insert($values); $db->insert_id 这种风格读取自增主键，
        //此处用公有属性暴露，由 insert() 方法在 wpdb->insert 成功后赋值
        public $insert_id = 0;

        public function __construct($table)
        {
            $this->table = $this->normalize_table($table);
        }

        public function distinct($distinct = true)
        {
            $this->distinct = (bool) $distinct;
            return $this;
        }

        public function field($fields)
        {
            $formatted = $this->format_fields($fields);
            if ($formatted !== '') {
                $this->fields = $formatted;
            }
            return $this;
        }

        public function where($field, $operator = null, $value = null)
        {
            if (func_num_args() === 1 && !is_array($field)) {
                if (is_numeric($field)) {
                    $this->add_where('id', '=', (int) $field);
                    return $this;
                }

                if (is_string($field) && trim($field) !== '') {
                    $this->where[] = trim($field);
                    return $this;
                }
            }

            if (is_array($field) && func_num_args() === 1) {
                foreach ($field as $key => $item) {
                    if (is_int($key) && is_array($item) && isset($item[0], $item[1])) {
                        $this->where($item[0], $item[1], $item[2] ?? null);
                        continue;
                    }

                    $this->where($key, $item);
                }
                return $this;
            }

            if (func_num_args() === 2) {
                $value = $operator;
                $operator = '=';
            }

            $this->add_where($field, $operator, $value);
            return $this;
        }

        public function whereTime($field, $range, $value = null)
        {
            $field_sql = $this->escape_identifier($field);
            $range = is_string($range) ? strtolower(trim($range)) : $range;

            if ($range === 'all' || $range === '' || $range === null) {
                return $this;
            }

            if ($range === 'between' && is_array($value) && isset($value[0], $value[1])) {
                $this->add_prepared_where("{$field_sql} BETWEEN %s AND %s", array($value[0], $value[1]));
                return $this;
            }

            if (is_array($range) && isset($range[0], $range[1])) {
                $this->add_prepared_where("{$field_sql} BETWEEN %s AND %s", array($range[0], $range[1]));
                return $this;
            }

            $time_range = $this->resolve_time_range((string) $range);
            if (!$time_range) {
                return $this;
            }

            if (!empty($time_range['operator'])) {
                $this->add_prepared_where("{$field_sql} {$time_range['operator']} %s", array($time_range['value']));
                return $this;
            }

            $this->add_prepared_where("{$field_sql} BETWEEN %s AND %s", array($time_range[0], $time_range[1]));
            return $this;
        }

        public function group($group)
        {
            $this->group = $this->format_group_or_order($group);
            return $this;
        }

        public function order($field, $direction = 'ASC')
        {
            if (!$field) {
                return $this;
            }

            if (func_num_args() === 1) {
                $this->order = $this->format_group_or_order($field);
                return $this;
            }

            $field_sql = $this->escape_identifier($field);
            $dir = strtoupper((string) $direction);
            $dir = in_array($dir, array('ASC', 'DESC'), true) ? $dir : 'ASC';
            $this->order = "{$field_sql} {$dir}";
            return $this;
        }

        public function limit($offset, $limit = null)
        {
            $offset = max(0, (int) $offset);

            if (func_num_args() > 1 && $limit !== null) {
                $limit = max(0, (int) $limit);
                $this->limit = $offset . ',' . $limit;
                return $this;
            }

            $this->limit = (string) $offset;
            return $this;
        }

        public function page($page, $list_rows = 20)
        {
            $page = max(1, (int) $page);
            $list_rows = max(1, (int) $list_rows);
            return $this->limit(($page - 1) * $list_rows, $list_rows);
        }

        public function getSql()
        {
            return $this->build_select_sql(false);
        }

        public function metaName($table)
        {
            $this->meta_table = $this->normalize_table($table);
            return $this;
        }

        //8.8 业务代码改用 metaTable 命名，提供别名兼容
        public function metaTable($table)
        {
            return $this->metaName($table);
        }

        public function metaQuery($meta_query, $join_keys = array('id', 'order_id'))
        {
            if (!$this->meta_table || !is_array($meta_query)) {
                return $this;
            }

            $main_key = $join_keys[0];
            $meta_fk = $join_keys[1];

            foreach ($meta_query as $index => $query) {
                if (empty($query['key'])) {
                    continue;
                }

                $alias = !empty($query['alias']) ? $query['alias'] : '_meta_' . $index;
                $meta_key_esc = esc_sql($query['key']);

                $on_clause = "{$this->table}.`{$main_key}` = `{$alias}`.`{$meta_fk}` AND `{$alias}`.`meta_key` = '{$meta_key_esc}'";

                $join_type = 'INNER';
                if (isset($query['value'])) {
                    $compare = isset($query['compare']) ? strtoupper(trim($query['compare'])) : '=';
                    $value = $query['value'];

                    if (($compare === 'IN' || $compare === 'NOT IN') && is_array($value)) {
                        $escaped = array_map('esc_sql', $value);
                        $in_list = "'" . implode("','", $escaped) . "'";
                        $on_clause .= " AND `{$alias}`.`meta_value` {$compare} ({$in_list})";
                    } elseif ($compare === 'BETWEEN' && is_array($value) && isset($value[0], $value[1])) {
                        $on_clause .= " AND `{$alias}`.`meta_value` BETWEEN '" . esc_sql($value[0]) . "' AND '" . esc_sql($value[1]) . "'";
                    } elseif (in_array($compare, array('=', '!=', '<>', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE'), true)) {
                        $on_clause .= " AND `{$alias}`.`meta_value` {$compare} '" . esc_sql($value) . "'";
                    }
                } else {
                    $join_type = 'LEFT';
                }

                $this->joins[] = "{$join_type} JOIN {$this->meta_table} AS `{$alias}` ON {$on_clause}";
            }

            return $this;
        }

        public function whereOr($field, $operator = null, $value = null)
        {
            if (is_numeric($field)) {
                $field_sql = (string) $field;
            } else {
                $field_sql = $this->escape_identifier($field);
            }

            $op = strtoupper(trim((string) $operator));

            if (($op === 'IN' || $op === 'NOT IN') && is_array($value)) {
                if (empty($value)) {
                    $this->or_conditions[] = '1 = 0';
                } else {
                    $escaped = array_map('esc_sql', $value);
                    $in_list = "'" . implode("','", $escaped) . "'";
                    $this->or_conditions[] = "{$field_sql} {$op} ({$in_list})";
                }
            } else {
                if (!in_array($op, array('=', '!=', '<>', '>', '>=', '<', '<='), true)) {
                    $op = '=';
                }
                $this->or_conditions[] = "{$field_sql} {$op} '" . esc_sql($value) . "'";
            }

            return $this;
        }

        public function whereLike($fields, $search_text)
        {
            if (!$search_text || !$fields) {
                return $this;
            }

            global $wpdb;
            $fields = (array) $fields;
            $escaped_like = $wpdb->esc_like($search_text);
            $like_parts = array();

            foreach ($fields as $field) {
                if (strpos($field, '.') !== false) {
                    $parts = explode('.', $field, 2);
                    $qualified = '`' . esc_sql($parts[0]) . '`.`' . esc_sql($parts[1]) . '`';
                    $like_parts[] = "{$qualified} LIKE '%" . esc_sql($escaped_like) . "%'";
                } else {
                    $field_sql = $this->escape_identifier($field);
                    $like_parts[] = "{$field_sql} LIKE '%" . esc_sql($escaped_like) . "%'";
                }
            }

            if ($like_parts) {
                $this->or_conditions = array_merge($this->or_conditions, $like_parts);
            }

            return $this;
        }

        public function find()
        {
            global $wpdb;

            $sql = $this->build_select_sql(true);
            $data = $wpdb->get_row($sql, ARRAY_A);

            return new ZibDBResult($data ?: array());
        }

        public function select()
        {
            global $wpdb;

            $sql = $this->build_select_sql(false);
            $data = $wpdb->get_results($sql, ARRAY_A);

            return new ZibDBResult($data ?: array());
        }

        public function count($field = '*')
        {
            global $wpdb;

            $field = $field ?: '*';
            $field_sql = $field === '*' ? '*' : $this->format_field_expression($field);
            $sql = $this->build_select_sql(false, 'COUNT(' . $field_sql . ')');

            return (int) $wpdb->get_var($sql);
        }

        public function value($field)
        {
            global $wpdb;

            $field_sql = $this->format_field_expression($field);
            $sql = $this->build_select_sql(true, $field_sql);

            return $wpdb->get_var($sql);
        }

        public function column($field, $key = '')
        {
            $rows = $this->field($field)->select()->toArray();
            if (!$rows) {
                return array();
            }

            $result = array();
            foreach ($rows as $row) {
                $row = (array) $row;
                $value = reset($row);

                if ($key && isset($row[$key])) {
                    $result[$row[$key]] = $value;
                } else {
                    $result[] = $value;
                }
            }

            return $result;
        }

        public function insert($data)
        {
            global $wpdb;

            $data = $this->normalize_data_array($data);
            if (!$data) {
                return false;
            }

            if (false === $wpdb->insert($this->table, $data)) {
                return false;
            }

            //8.8 业务代码会读 $db->insert_id；同时也通过返回值 true/false 指示成功
            $this->insert_id = (int) $wpdb->insert_id;
            return true;
        }

        public function insertGetId($data)
        {
            global $wpdb;

            $data = $this->normalize_data_array($data);
            if (!$data) {
                return false;
            }

            if (false === $wpdb->insert($this->table, $data)) {
                return false;
            }

            return (int) $wpdb->insert_id;
        }

        public function update($data)
        {
            global $wpdb;

            $data = $this->normalize_data_array($data);
            if (!$data) {
                return false;
            }

            $set_parts = array();
            $values = array();

            foreach ($data as $field => $value) {
                $set_parts[] = $this->escape_identifier($field) . ' = ' . (is_numeric($value) ? '%d' : '%s');
                $values[] = $value;
            }

            $sql = "UPDATE {$this->table} SET " . implode(', ', $set_parts);
            if ($this->where) {
                $sql .= ' WHERE ' . implode(' AND ', $this->where);
                $values = array_merge($values, $this->where_values);
            }

            $sql = $wpdb->prepare($sql, $values);
            return false !== $wpdb->query($sql);
        }

        public function delete()
        {
            global $wpdb;

            $sql = "DELETE FROM {$this->table}";
            if ($this->where) {
                $sql .= ' WHERE ' . implode(' AND ', $this->where);
                $sql = $wpdb->prepare($sql, $this->where_values);
            }

            return false !== $wpdb->query($sql);
        }

        protected function add_where($field, $operator, $value)
        {
            if (!is_string($field) || trim($field) === '') {
                return;
            }

            $field_sql = $this->escape_identifier($field);
            $operator = strtoupper(trim((string) $operator));
            $operator = $operator ?: '=';

            if (is_null($value)) {
                if (in_array($operator, array('!=', '<>', 'IS NOT'), true)) {
                    $this->where[] = "{$field_sql} IS NOT NULL";
                } else {
                    $this->where[] = "{$field_sql} IS NULL";
                }
                return;
            }

            if ($operator === 'BETWEEN' && is_array($value) && isset($value[0], $value[1])) {
                $this->add_prepared_where("{$field_sql} BETWEEN %s AND %s", array($value[0], $value[1]));
                return;
            }

            if (is_array($value)) {
                if (!$value) {
                    $this->where[] = in_array($operator, array('NOT IN', 'NOT BETWEEN'), true) ? '1 = 1' : '1 = 0';
                    return;
                }

                if (!in_array($operator, array('IN', 'NOT IN'), true)) {
                    $operator = 'IN';
                }

                $placeholders = array();
                foreach ($value as $item) {
                    $placeholders[] = is_numeric($item) ? '%d' : '%s';
                    $this->where_values[] = $item;
                }

                $this->where[] = "{$field_sql} {$operator} (" . implode(',', $placeholders) . ')';
                return;
            }

            if (!in_array($operator, array('=', '!=', '<>', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE'), true)) {
                $operator = '=';
            }

            $this->where[] = "{$field_sql} {$operator} " . (is_numeric($value) ? '%d' : '%s');
            $this->where_values[] = $value;
        }

        protected function add_prepared_where($condition, $values = array())
        {
            $this->where[] = $condition;
            foreach ((array) $values as $value) {
                $this->where_values[] = $value;
            }
        }

        protected function build_select_sql($find = false, $fields = null)
        {
            global $wpdb;

            $select = $fields ?: $this->fields;
            $distinct = $this->distinct ? 'DISTINCT ' : '';
            $sql = "SELECT {$distinct}{$select} FROM {$this->table}";

            if ($this->joins) {
                $sql .= ' ' . implode(' ', $this->joins);
            }

            $all_where = $this->where;
            if ($this->or_conditions) {
                $all_where[] = '(' . implode(' OR ', $this->or_conditions) . ')';
            }

            if ($all_where) {
                $sql .= ' WHERE ' . implode(' AND ', $all_where);
            }

            if ($this->group) {
                $sql .= ' GROUP BY ' . $this->group;
            }

            if ($this->order) {
                $sql .= ' ORDER BY ' . $this->order;
            }

            if ($find) {
                $sql .= ' LIMIT 1';
            } elseif ($this->limit !== '') {
                $sql .= ' LIMIT ' . $this->limit;
            }

            if ($this->where_values) {
                $sql = $wpdb->prepare($sql, $this->where_values);
            }

            return $sql;
        }

        protected function format_fields($fields)
        {
            if (is_string($fields)) {
                return trim($fields);
            }

            if (!is_array($fields) || !$fields) {
                return '';
            }

            $result = array();
            foreach ($fields as $field => $alias) {
                if (is_int($field)) {
                    $result[] = $this->format_field_expression($alias);
                    continue;
                }

                $field_sql = $this->format_field_expression($field);
                $alias_sql = $this->escape_alias($alias);
                $result[] = $alias_sql ? "{$field_sql} AS {$alias_sql}" : $field_sql;
            }

            return implode(', ', array_filter($result));
        }

        protected function format_field_expression($field)
        {
            $field = trim((string) $field);
            if ($field === '' || $field === '*') {
                return $field;
            }

            if (preg_match('/[`()\\s,\'"]/', $field)) {
                return $field;
            }

            return $this->escape_identifier($field);
        }

        protected function format_group_or_order($value)
        {
            if (is_array($value)) {
                $parts = array();
                foreach ($value as $item) {
                    $parts[] = $this->format_group_or_order($item);
                }
                return implode(', ', array_filter($parts));
            }

            $value = trim((string) $value);
            if ($value === '') {
                return '';
            }

            if (preg_match('/[`()\\s,\'"]/', $value)) {
                return $value;
            }

            return $this->escape_identifier($value);
        }

        protected function resolve_time_range($range)
        {
            $timestamp = current_time('timestamp');

            switch ($range) {
                case 'last_10_minutes':
                    return array(
                        'operator' => '>=',
                        'value'    => date('Y-m-d H:i:s', $timestamp - 10 * MINUTE_IN_SECONDS),
                    );

                case 'last_1_day':
                    return array(
                        'operator' => '>=',
                        'value'    => date('Y-m-d H:i:s', $timestamp - DAY_IN_SECONDS),
                    );

                case 'today':
                    return array(
                        date('Y-m-d 00:00:00', $timestamp),
                        date('Y-m-d 23:59:59', $timestamp),
                    );

                case 'yester':
                case 'yesterday':
                    $yesterday = strtotime('-1 day', $timestamp);
                    return array(
                        date('Y-m-d 00:00:00', $yesterday),
                        date('Y-m-d 23:59:59', $yesterday),
                    );

                case 'thismonth':
                    return array(
                        date('Y-m-01 00:00:00', $timestamp),
                        date('Y-m-t 23:59:59', $timestamp),
                    );

                case 'lastmonth':
                    $last_month = strtotime(date('Y-m-01 00:00:00', $timestamp) . ' -1 day');
                    return array(
                        date('Y-m-01 00:00:00', $last_month),
                        date('Y-m-t 23:59:59', $last_month),
                    );

                case 'thisyear':
                    return array(
                        date('Y-01-01 00:00:00', $timestamp),
                        date('Y-12-31 23:59:59', $timestamp),
                    );
            }

            return null;
        }

        protected function normalize_data_array($data)
        {
            if (!is_array($data)) {
                return array();
            }

            $normalized = array();
            foreach ($data as $field => $value) {
                if (!is_string($field) || $field === '') {
                    continue;
                }
                $normalized[$field] = $value;
            }

            return $normalized;
        }

        protected function normalize_table($table)
        {
            global $wpdb;

            $table = trim((string) $table);
            if ($table === '') {
                return $table;
            }

            if (isset($wpdb->$table) && is_string($wpdb->$table) && $wpdb->$table !== '') {
                return $wpdb->$table;
            }

            if (strpos($table, $wpdb->prefix) === 0 || strpos($table, '.') !== false) {
                return $table;
            }

            return $wpdb->prefix . ltrim($table, '_');
        }

        protected function escape_identifier($identifier)
        {
            $identifier = trim((string) $identifier);
            if ($identifier === '' || $identifier === '*') {
                return $identifier;
            }

            if (preg_match('/[`()\\s,\'"]/', $identifier)) {
                return $identifier;
            }

            $parts = explode('.', $identifier);
            $parts = array_map(function ($part) {
                $part = preg_replace('/[^A-Za-z0-9_]/', '', $part);
                return $part === '' ? '' : '`' . $part . '`';
            }, $parts);

            return implode('.', array_filter($parts, 'strlen'));
        }

        protected function escape_alias($alias)
        {
            $alias = preg_replace('/[^A-Za-z0-9_]/', '', (string) $alias);
            return $alias === '' ? '' : '`' . $alias . '`';
        }
    }
}

if (!class_exists('ZibDB')) {
    class ZibDB
    {
        public static function name($table)
        {
            return new ZibDBQuery($table);
        }

        public static function table($table)
        {
            return new ZibDBQuery($table);
        }
    }
}
