<?php

namespace Drupal\allow_only_one\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Prevent node/term creation if field value combination already exists.
 *
 * @Constraint(
 *   id = "AllowOnlyOne",
 *   label = @Translation("Prevent node/term creation if field value combination already exists.", context = "Validation"),
 *   type = { "allow_only_one" }
 * )
 */
class AllowOnlyOneConstraint extends Constraint {

  /**
   * Message if create a node/term with existing combination of field values.
   *
   * @var string
   */
  public $entityMessage = 'There is existing content: <a href=":url" target="_blank">@title</a>, with identical values for the following field(s): %label.';

}
