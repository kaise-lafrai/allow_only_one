
## Allow Only One
The Allow Only One module was created to prevent duplicate content save, based
on a combination of field values. This module provides a new field type that
stores configuration and is later used during validation. Important: This only
works and has been tested on Node and Taxonomy_Term

Some content strategies rely on there being only one of something. Yet how we
define "one" is sometime more complicated.

Example - too simple:  Car names. There should only be one car with a given
name. (unique by title)
  * Corvette
  * Forrester

Example - increased complexity: Some cars have the same name, but not from the
same company. (unique by maker and title)
  * Chevrolet, Suburban
  * Plymouth, Suburban

Example - real complexity: Some cars by the same name and the same company were
different based on where they were made
  * American, Ford, Grenada
  * European, Ford, Grenada

If you were creating a system related to cars and you wanted to enforce
uniqueness, it would not be as simple as just preventing a duplicate title,
you'd have to prevent duplicate of the unique combination of Location, maker and
name.  This is the kind thing this module can help with.

Allow Only One allows you to prevent duplicate content from being saved based
on a combination of fields on the node or term.  It also allow you to prevent
duplicates on multiple combinations of data.

Example: A news site that never wants two articles to have the same name. AND
does not want more than one article tagged with the same topic and state per
day.  You could create:
   1. An allow_only_one field that prevents a duplicate title.
   2. An allow_only_one field that prevents a duplicate combination of topic,
   state, and release date fields.
Those two validations would run completely separate of each other.

## Setup
1. Enable Allow Only One module.
2. Manage the fields on your content type or vocabulary and add a field of type
"Allow Only One".
3. In the settings for the field, check off the combination of fields you want
to prevent from being duplicated. Remember, you can add multiple Allow Only One
fields if you need distinct validation.
4. The field will not show on entity view, or on the edit form. However, the
validation error messages appear where the field is positioned on the form
display.  It is recommended to move the Allow Only One field to just above or
below the first field that is used in the validation.
Examples:
   * If you are validating for unique title, move the field near the title
   field.
   * If you are validating for unique topic tag and state, move the field near
  the topic tag.

## How does it work?
On node save, it performs a database query to see if there are any other content
 of the same type that have the same combination of values.

## Caveats / Limitations
1.  This is only designed to work with single value fields (cardinality = 1).
It can't handle using fields that allow multiple values.
2.  This does not prevent cloning (cloning does not run validation).  It will
however prevent saving an unaltered clone.
