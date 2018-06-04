<?php

class RedirectedURLCsvBulkLoader extends CsvBulkLoader
{
    /**
     * overriding this here for handling ValidationException when DataObject::write() called
     *
     * @param array $record
     * @param array $columnMap
     * @param BulkLoader_Result $results
     * @param boolean $preview
     *
     * @return int - RedirectedURL object ID
     */
    protected function processRecord($record, $columnMap, &$results, $preview = false) {
        // make $exceptionThrown false initially
        $exceptionThrown = false;

        $class = $this->objectClass;

        // find existing object, or create new one
        $existingObj = $this->findExistingObject($record, $columnMap);
        $obj = ($existingObj) ? $existingObj : new $class();

        // first run: find/create any relations and store them on the object
        // we can't combine runs, as other columns might rely on the relation being present
        $relations = array();
        foreach($record as $fieldName => $val) {
            // don't bother querying of value is not set
            if($this->isNullValue($val)) {
                continue;
            };

            // checking for existing relations
            if(isset($this->relationCallbacks[$fieldName])) {
                // trigger custom search method for finding a relation based on the given value
                // and write it back to the relation (or create a new object)
                $relationName = $this->relationCallbacks[$fieldName]['relationname'];
                if($this->hasMethod($this->relationCallbacks[$fieldName]['callback'])) {
                    $relationObj = $this->{$this->relationCallbacks[$fieldName]['callback']}($obj, $val, $record);
                } elseif($obj->hasMethod($this->relationCallbacks[$fieldName]['callback'])) {
                    $relationObj = $obj->{$this->relationCallbacks[$fieldName]['callback']}($val, $record);
                }
                if(!$relationObj || !$relationObj->exists()) {
                    $relationClass = $obj->hasOneComponent($relationName);
                    $relationObj = new $relationClass();
                    //write if we aren't previewing
                    if (!$preview) {
                        try {
                            $relationObj->write();
                        } catch (ValidationException $e) {
                            $exceptionThrown = true;
                        }
                    };
                }
                $obj->{"{$relationName}ID"} = $relationObj->ID;
                //write if we are not previewing
                if (!$preview) {
                    try {
                        $obj->write();
                    } catch (ValidationException $e) {
                        $exceptionThrown = true;
                    }

                    $obj->flushCache(); // avoid relation caching confusion
                }

            } elseif(strpos($fieldName, '.') !== false) {
                // we have a relation column with dot notation
                list($relationName, $columnName) = explode('.', $fieldName);
                // always gives us an component (either empty or existing)
                $relationObj = $obj->getComponent($relationName);
                if (!$preview)
                    try {
                        $relationObj->write();
                    } catch (ValidationException $e) {
                        $exceptionThrown = true;
                    }

                $obj->{"{$relationName}ID"} = $relationObj->ID;

                //write if we are not previewing
                if (!$preview) {
                    try {
                        $obj->write();
                    } catch (ValidationException $e) {
                        $exceptionThrown = true;
                    }

                    $obj->flushCache(); // avoid relation caching confusion
                }
            }
        }

        // second run: save data

        foreach($record as $fieldName => $val) {
            // break out of the loop if we are previewing
            if ($preview) {
                break;
            }

            // look up the mapping to see if this needs to map to callback
            $mapped = $this->columnMap && isset($this->columnMap[$fieldName]);

            if($mapped && strpos($this->columnMap[$fieldName], '->') === 0) {
                $funcName = substr($this->columnMap[$fieldName], 2);

                $this->$funcName($obj, $val, $record);
            } else if($obj->hasMethod("import{$fieldName}")) {
                $obj->{"import{$fieldName}"}($val, $record);
            } else {
                $obj->update(array($fieldName => $val));
            }
        }

        // write record
        if (!$preview) {
            try {
                $obj->write();
            } catch (ValidationException $e) {
                $exceptionThrown = true;
            }
        }

        // @todo better message support
        $message = '';

        // save to results
        if (!$exceptionThrown) {
            if($existingObj) {
                $results->addUpdated($obj, $message);
            } else {
                $results->addCreated($obj, $message);
            }
        }

        $objID = $obj->ID;

        $obj->destroy();

        // memory usage
        unset($existingObj);
        unset($obj);

        return $objID;
    }

}