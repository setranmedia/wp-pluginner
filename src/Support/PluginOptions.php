<?php

namespace SetranMedia\WpPluginner\Support;

use SetranMedia\WpPluginner\Model\WpOption;

if ( ! defined( 'ABSPATH' ) ) exit;

class PluginOptions implements \ArrayAccess
{

    protected $optionsSave = false;
    protected $optionsName = null;
    protected $optionsData = [];


    /**
    * Decoded json form option_value.
    *
    * @var array
    */
    private $_value = [];

    /**
    * Create a new WordPressOption.
    *
    * @param $plugin
    */
    public function __construct( $config = null )
    {
        $this->optionsSave = isset($config['save']) ? $config['save'] : false;
        $this->optionsName = isset($config['name']) ? $config['name'] : false;
        $this->optionsData = isset($config['data']) && is_array($config['data']) ? $config['data'] : [];
        if (
            $this->optionsName &&
            is_array($this->optionsData) &&
            !empty($this->optionsData)
        ) {
            $this->_value = $this->optionsData;
            $options = WpOption::firstOrCreate(
                ['option_name' => $this->optionsName],
                ['option_value' => $this->optionsData]
            );
            if (
                $options &&
                isset($options->option_value) &&
                $options->option_value
            ) {
                $this->_value = (array) json_decode( $options->option_value, true );
            }
        }
    }

    /**
    * Get the string representation (json) of the options.
    *
    * @return string
    */
    public function __toString()
    {
        return json_encode( $this->_value, JSON_PRETTY_PRINT );
    }

    /**
    * Return the flat array of the options.
    *
    * @return array
    */
    public function toArray(  )
    {
        return $this->_value;
    }

    /**
    * Return a branch/single option by path.
    *
    * @param string $path    The path option.
    * @param string $default Optional. A default value if the option doesn't exists.
    *
    * @example
    *
    *     echo $plugin->options->get( 'General.doNotExists', 'default');
    *
    * @return array|mixed|string
    */
    public function get( $path, $default = "" )
    {
        $path = str_replace( '/', '.', $path );
        $keys = explode( ".", $path );

        $current = $this->_value;

        foreach ( $keys as $key ) {

            if ( ! isset( $current[ $key ] ) ) {
                return $default;
            }

            if ( is_object( $current[ $key ] ) ) {
                $current = (array) $current[ $key ];
            }
            else {
                $current = $current[ $key ];
            }
        }

        return $current;

    }

    /**
    * Set (or remove) a branch/single option by path.
    *
    * @param string $path  The path of the option.
    * @param mixed  $value Optional. value to set, if null the option will be removed.
    *
    * @return array|null
    */
    public function set( $path, $value = null )
    {
        if ( is_null( $value ) ) {
            return $this->delete( $path );
        }

        $path = str_replace( '/', '.', $path );
        $keys = explode( ".", $path );

        $copy = $this->_value;

        $array = &$copy;

        foreach ( $keys as $key ) {
            if ( ! isset( $array[ $key ] ) ) {
                $array[ $key ] = '';
            }

            $array = &$array[ $key ];
        }

        $array = $value;

        $this->update( $copy );

        return $value;
    }


    public function offsetSet( $offset, $value )
    {
        $this->set( $offset, $value );
    }

    public function offsetExists( $offset )
    {
        return ! is_null( $this->get( $offset, null ) );
    }

    public function offsetUnset( $offset )
    {
        $this->set( $offset );
    }

    public function offsetGet( $offset )
    {
        return $this->get( $offset );
    }





    /**
    * Delete a branch/single option by path.
    *
    * @param string $path The path of the option to delete.
    *
    * @return array
    */
    public function delete( $path = '' )
    {
        if ( empty( $path ) ) {
            $this->_value = [];
        }
        else {
            $path = str_replace( '/', '.', $path );
            $keys = explode( ".", $path );

            $lastKey = $keys[ count( $keys ) - 1 ];

            $array = &$this->_value;

            foreach ( $keys as $key ) {
                if ( $key == $lastKey ) {
                    unset( $array[ $key ] );
                    break;
                }
                $array = &$array[ $key ];
            }
        }
        $result = WpOption::updateOrCreate(
            ['option_name' => $this->optionsName],
            ['option_value' => json_encode( $this->_value )]
        );

        return $this->_value;
    }

    /**
    * Update a branch of options.
    *
    * @param array $options
    *
    * @return false|int
    */
    public function update( $options = [] )
    {

        if ( is_null( $this->row ) ) {
            return $this->reset();
        }

        $mergeOptions = array_replace_recursive( $this->_value, $options );

        $result = WpOption::updateOrCreate(
            ['option_name' => $this->optionsName],
            ['option_value' => json_encode( $mergeOptions )]
        );


        $this->_value = (array) $mergeOptions;

        return $result;

    }

    /**
    * Execute a delta from the current version of the options and the previous version stored in the database.
    *
    * @return false|int
    */
    public function delta()
    {

        $mergeOptions = $this->__delta( $this->optionsData, $this->_value );


        $result = WpOption::updateOrCreate(
            ['option_name' => $this->optionsName],
            ['option_value' => json_encode( $mergeOptions )]
        );


        $this->_value = (array) $mergeOptions;

        return $result;

    }

    /**
    * Load the default value from `config/options.php` and replace the current.
    *
    * @return false|int
    */
    public function reset()
    {

        $result = WpOption::updateOrCreate(
            ['option_name' => $this->optionsName],
            ['option_value' => json_encode( $this->optionsData )]
        );


        $this->_value = (array) $mergeOptions;

        return $result;

    }

    /**
    * Do a merge/combine between two object tree.
    * If the old version not contains an object or property, that is added.
    * If the old version contains an object or property less in last version, that it will be deleted.
    *
    * @param mixed $lastVersion Object tree with new or delete object/value
    * @param mixed $result      Current Object tree, loaded from serialize or database for example
    *
    * @return Object the delta Object tree
    */
    private function __delta( array $lastVersion, &$result )
    {
        // search for new
        foreach ( $lastVersion as $key => $value ) {
            if ( ! is_numeric( $key ) && ! isset( $result[ $key ] ) ) {
                $result[ $key ] = $value;
            }

            if ( is_array( $value ) && ! is_numeric( $key ) ) {
                $result[ $key ] = $this->__delta( $lastVersion[ $key ], $result[ $key ] );
            }
        }

        // serach for delete
        foreach ( $result as $key => $value ) {
            if ( ! is_numeric( $key ) && ! isset( $lastVersion[ $key ] ) ) {
                unset( $result[ $key ] );
            }
        }

        return $result;
    }
}
