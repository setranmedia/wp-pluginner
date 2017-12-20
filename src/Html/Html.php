<?php

namespace SetranMedia\WpPluginner\Html;

class Html
{

  protected static $htmlTags = [
    'a'        => '\SetranMedia\WpPluginner\Html\HtmlTagA',
    'button'   => '\SetranMedia\WpPluginner\Html\HtmlTagButton',
    'checkbox' => '\SetranMedia\WpPluginner\Html\HtmlTagCheckbox',
    'datetime' => '\SetranMedia\WpPluginner\Html\HtmlTagDatetime',
    'fieldset' => '\SetranMedia\WpPluginner\Html\HtmlTagFieldSet',
    'form'     => '\SetranMedia\WpPluginner\Html\HtmlTagForm',
    'input'    => '\SetranMedia\WpPluginner\Html\HtmlTagInput',
    'label'    => '\SetranMedia\WpPluginner\Html\HtmlTagLabel',
    'optgroup' => '\SetranMedia\WpPluginner\Html\HtmlTagOptGroup',
    'option'   => '\SetranMedia\WpPluginner\Html\HtmlTagOption',
    'select'   => '\SetranMedia\WpPluginner\Html\HtmlTagSelect',
    'textarea' => '\SetranMedia\WpPluginner\Html\HtmlTagTextArea',
  ];

  public static function __callStatic( $name, $arguments )
  {
    if ( in_array( $name, array_keys( self::$htmlTags ) ) ) {
      $args = ( isset( $arguments[ 0 ] ) && ! is_null( $arguments[ 0 ] ) ) ? $arguments[ 0 ] : [];

      return new self::$htmlTags[ $name ]( $args );
    }
  }
}
