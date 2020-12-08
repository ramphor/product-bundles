<?php
namespace Ramphor\ProductBundles\WooCommerce\Data;

use Ramphor\ProductBundles\WooCommerce\Data\BundledItemData;

class BundledItemData
{
    protected $data = array(
        'bundled_item_id' => 0,
        'product_id'      => 0,
        'bundle_id'       => 0,
        'menu_order'      => 0
    );

    /**
     * Stores meta data, defaults included.
     * Meta keys are assumed unique by default. No meta is internal.
     *
     * @var array
     */
    protected $meta_data = array(
        'quantity_min'                          => 1,
        'quantity_max'                          => 1,
        'priced_individually'                   => 'no',
        'shipped_individually'                  => 'no',
        'override_title'                        => 'no',
        'title'                                 => '',
        'override_description'                  => 'no',
        'description'                           => '',
        'optional'                              => 'no',
        'hide_thumbnail'                        => 'no',
        'discount'                              => null,
        'override_variations'                   => 'no',
        'override_default_variation_attributes' => 'no',
        'allowed_variations'                    => null,
        'default_variation_attributes'          => null,
        'single_product_visibility'             => 'visible',
        'cart_visibility'                       => 'visible',
        'order_visibility'                      => 'visible',
        'single_product_price_visibility'       => 'visible',
        'cart_price_visibility'                 => 'visible',
        'order_price_visibility'                => 'visible',
        'stock_status'                          => null,
        'max_stock'                             => null
    );

    protected $meta_data_type_fn = array(
        'quantity_min'                          => 'absint',
        'quantity_max'                          => 'absint_if_not_empty',
        'priced_individually'                   => 'yes_or_no',
        'shipped_individually'                  => 'yes_or_no',
        'override_title'                        => 'yes_or_no',
        'title'                                 => 'strval',
        'override_description'                  => 'yes_or_no',
        'description'                           => 'strval',
        'optional'                              => 'yes_or_no',
        'hide_thumbnail'                        => 'yes_or_no',
        'discount'                              => 'double_if_not_empty',
        'override_variations'                   => 'yes_or_no',
        'override_default_variation_attributes' => 'yes_or_no',
        'allowed_variations'                    => 'maybe_unserialize',
        'default_variation_attributes'          => 'maybe_unserialize',
        'single_product_visibility'             => 'visible_or_hidden',
        'cart_visibility'                       => 'visible_or_hidden',
        'order_visibility'                      => 'visible_or_hidden',
        'single_product_price_visibility'       => 'visible_or_hidden',
        'cart_price_visibility'                 => 'visible_or_hidden',
        'order_price_visibility'                => 'visible_or_hidden',
        'stock_status'                          => 'strval',
        'max_stock'                             => 'absint_if_not_empty'
    );

    public function __construct($item = 0)
    {
        if ($item instanceof BundledItemData) {
            $this->set_all($item->get_data());
        } elseif (is_array($item)) {
            $this->set_all($item);
        } else {
            $this->read($item);
        }
    }

    public function __toString()
    {
        return json_encode($this->get_data());
    }

    public function set_all($data)
    {
        foreach ($data as $key => $value) {
            if (is_callable(array( $this, "set_$key" ))) {
                $this->{"set_$key"}($value);
            } else {
                $this->data[ $key ] = $value;
            }
        }
    }

    public function get_data()
    {
        return array_merge($this->data, array( 'meta_data' => $this->get_meta_data() ));
    }

    public function get_meta_data()
    {
        return array_filter($this->meta_data, array( $this, 'has_meta_value' ));
    }

    public function set_id($value)
    {
        $this->set_bundled_item_id($value);
    }

    public function get_id()
    {
        return is_object($this->data) ? $this->data->get_id() : null;
    }

    public function set_bundled_item_id($value)
    {
        $this->data[ 'bundled_item_id' ] = absint($value);
    }

    public function get_bundle_id()
    {
        return absint($this->data[ 'bundle_id' ]);
    }

    public function get_product_id()
    {
        return absint($this->data[ 'product_id' ]);
    }

    public function update_meta($key, $value)
    {
        if (is_null($value)) {
            $this->delete_meta($key);
        } else {
            $this->meta_data[ $key ] = $this->sanitize_meta_value($value, $key);
        }
    }

    private function sanitize_meta_value($meta_value, $meta_key)
    {

        // If the key is known, apply known sanitization function.
        if (isset($this->meta_data_type_fn[ $meta_key ])) {
            $fn = $this->meta_data_type_fn[ $meta_key ];

            if ('yes_or_no' === $fn) {
                // 'no' by default.
                if (is_bool($meta_value)) {
                    $meta_value = true === $meta_value ? 'yes' : 'no';
                } else {
                    $meta_value = 'yes' === $meta_value ? 'yes' : 'no';
                }
            } elseif ('visible_or_hidden' === $fn) {
                // 'visible' by default.
                $meta_value = 'hidden' === $meta_value ? 'hidden' : 'visible';
            } elseif ('absint_if_not_empty' === $fn) {
                $meta_value = '' !== $meta_value ? absint($meta_value) : '';
            } elseif ('double_if_not_empty' === $fn) {
                $meta_value = '' !== $meta_value  ? doubleval($meta_value) : '';
            } elseif (function_exists($fn)) {
                $meta_value = $fn($meta_value);
            }

        // Otherwise, always attempt to unserialize on the way in.
        } else {
            $meta_value = maybe_unserialize($meta_value);
        }

        return $meta_value;
    }

    /**
     * Cleans null value meta when getting.
     *
     * @param  mixed  $value
     * @return boolean
     */
    private function has_meta_value($value)
    {
        return ! is_null($value);
    }
}
