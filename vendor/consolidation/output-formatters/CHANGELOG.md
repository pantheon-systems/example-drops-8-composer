# Change Log

### 3.1.5 - 23 November 2016

- When converting from XML to an array, use the 'id' or 'name' element as the array key value.

### 3.1.4 - 20 November 2016

- Add a 'list delimiter' formatter option, so that we can create a Drush-style table for property lists.

### 3.1.1 ~ 3.1.3 - 18 November 2016

- Fine-tune wordwrapping.

### 3.1.0 - 17 November 2016

- Add wordwrapping to table formatter.

### 3.0.0 - 14 November 2016

- **Breaking** The RenderCellInterface is now provided a reference to the entire row data. Existing clients need only add the new parameter to their method defnition to update.
- Rename AssociativeList to PropertyList, as many people seemed to find the former name confusing. AssociativeList is still available for use to preserve backwards compatibility, but it is deprecated.


### 2.1.0 - 7 November 2016

- Add RenderCellCollections to structured lists, so that commands may add renderers to structured data without defining a new structured data subclass.
- Throw an exception if the client requests a field that does not exist.
- Remove unwanted extra layer of nesting when formatting an PropertyList with an array formatter (json, yaml, etc.).


### 2.0.0 - 30 September 2016

- **Breaking** The default `string` format now converts non-string results into a tab-separated-value table if possible.  Commands may select a single field to emit in this instance with an annotation: `@default-string-field email`.  By this means, a given command may by default emit a single value, but also provide more rich output that may be shown by selecting --format=table, --format=yaml or the like.  This change might cause some commands to produce output in situations that previously were not documented as producing output.
- **Breaking** FormatterManager::addFormatter() now takes the format identifier and a FormatterInterface, rather than an identifier and a Formatter classname (string).
- --field is a synonym for --fields with a single field.
- Wildcards and regular expressions can now be used in --fields expressions.


### 1.1.0 - 14 September 2016

Add tab-separated-value (tsv) formatter.


### 1.0.0 - 19 May 2016

First stable release.
