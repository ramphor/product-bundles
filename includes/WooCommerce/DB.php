<?php
namespace Ramphor\ProductBundles\WooCommerce;

class DB
{
    public static function query_bundled_items($args)
    {

        global $wpdb;

        $args = wp_parse_args($args, array(
            'return'          => 'all', // 'ids' | 'id=>bundle_id' | 'id=>product_id' | 'objects' | 'count'
            'bundled_item_id' => 0,
            'product_id'      => 0,
            'bundle_id'       => 0,
            'order_by'        => array( 'bundled_item_id' => 'ASC' ),
            'meta_query'      => array()
        ));

        $table = $wpdb->prefix . 'woocommerce_bundled_items';

        if (in_array($args[ 'return' ], array( 'ids', 'objects' ))) {
            $select = $table . '.bundled_item_id';
        } elseif ('count' === $args[ 'return' ]) {
            $select = 'COUNT(' . $table . '.bundled_item_id' . ')';
        } elseif ('id=>bundle_id' === $args[ 'return' ]) {
            $select = $table . '.bundled_item_id, ' . $table . '.bundle_id';
        } else {
            $select = '*';
        }

        $sql      = "SELECT " . $select . " FROM {$table}";
        $join     = '';
        $where    = '';
        $order_by = '';

        $where_clauses    = array( '1=1' );
        $order_by_clauses = array();

        // WHERE clauses.

        if ($args[ 'bundled_item_id' ]) {
            $bundled_item_ids = array_map('absint', is_array($args[ 'bundled_item_id' ]) ? $args[ 'bundled_item_id' ] : array( $args[ 'bundled_item_id' ] ));
            $where_clauses[]  = "{$table}.bundled_item_id IN (" . implode(",", array_map('esc_sql', $bundled_item_ids)) . ")";
        }

        if ($args[ 'product_id' ]) {
            $product_ids     = array_map('absint', is_array($args[ 'product_id' ]) ? $args[ 'product_id' ] : array( $args[ 'product_id' ] ));
            $where_clauses[] = "{$table}.product_id IN (" . implode(',', array_map('esc_sql', $product_ids)) . ")";
        }

        if ($args[ 'bundle_id' ]) {
            $bundle_ids      = array_map('absint', is_array($args[ 'bundle_id' ]) ? $args[ 'bundle_id' ] : array( $args[ 'bundle_id' ] ));
            $where_clauses[] = "{$table}.bundle_id IN (" . implode(",", array_map('esc_sql', $bundle_ids)) . ")";
        }

        // ORDER BY clauses.

        if ($args[ 'order_by' ] && is_array($args[ 'order_by' ])) {
            foreach ($args[ 'order_by' ] as $what => $how) {
                $order_by_clauses[] = $table . '.' . esc_sql(strval($what)) . " " . esc_sql(strval($how));
            }
        }

        $order_by_clauses = empty($order_by_clauses) ? array( $table . '.bundled_item_id, ASC' ) : $order_by_clauses;

        // Build SQL query components.

        $where    = ' WHERE ' . implode(' AND ', $where_clauses);
        $order_by = ' ORDER BY ' . implode(', ', $order_by_clauses);

        // Append meta query SQL components.

        if ($args[ 'meta_query' ] && is_array($args[ 'meta_query' ])) {
            $meta_query = new WP_Meta_Query();

            $meta_query->parse_query_vars($args);

            $meta_sql = $meta_query->get_sql('bundled_item', $table, 'bundled_item_id');

            if (! empty($meta_sql)) {
                // Meta query JOIN clauses.
                if (! empty($meta_sql[ 'join' ])) {
                    $join = $meta_sql[ 'join' ];
                }
                // Meta query WHERE clauses.
                if (! empty($meta_sql[ 'where' ])) {
                    $where .= $meta_sql[ 'where' ];
                }
            }
        }

        // Assemble and run the query.

        $sql .= $join . $where . $order_by;

        if ('count' === $args[ 'return' ]) {
            $result = $wpdb->get_var($sql);

            return $result ? $result : 0;
        }

        $results = $wpdb->get_results($sql);

        if (empty($results)) {
            return array();
        }

        $a = array();

        if ('objects' === $args[ 'return' ]) {
            foreach ($results as $result) {
                $a[] = self::get_bundled_item($result->bundled_item_id);
            }
        } elseif ('ids' === $args[ 'return' ]) {
            foreach ($results as $result) {
                $a[] = $result->bundled_item_id;
            }
        } elseif ('id=>bundle_id' === $args[ 'return' ]) {
            foreach ($results as $result) {
                $a[ $result->bundled_item_id ] = $result->bundle_id;
            }
        } elseif ('id=>product_id' === $args[ 'return' ]) {
            foreach ($results as $result) {
                $a[ $result->bundled_item_id ] = $result->product_id;
            }
        } else {
            foreach ($results as $result) {
                $a[] = (array) $result;
            }
        }

        return $a;
    }
}
