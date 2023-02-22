<?php

namespace Drupal\allow_only_one\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'AllowOnlyOne' formatter.
 *
 * @FieldFormatter(
 *   id = "allow_only_one",
 *   label = @Translation("Allow Only One"),
 *   field_types = {
 *     "allow_only_one"
 *   }
 * )
 */
class AllowOnlyOneFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    return [];
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // No value is saved, nothing is outputted.
    return NULL;
  }

}
