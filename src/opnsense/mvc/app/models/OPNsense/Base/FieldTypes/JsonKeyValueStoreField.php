<?php

/**
 *    Copyright (C) 2015 Deciso B.V.
 *
 *    All rights reserved.
 *
 *    Redistribution and use in source and binary forms, with or without
 *    modification, are permitted provided that the following conditions are met:
 *
 *    1. Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *    2. Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 *    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 *    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 *    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 *    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 *    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 *    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 *    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 *    POSSIBILITY OF SUCH DAMAGE.
 *
 */
namespace OPNsense\Base\FieldTypes;

use Phalcon\Validation\Validator\InclusionIn;
use OPNsense\Base\Validators\CsvListValidator;

/**
 * Class JsonKeyValueStoreField, use a json encoded file as selection list
 * @package OPNsense\Base\FieldTypes
 */
class JsonKeyValueStoreField extends BaseField
{
    /**
     * @var bool marks if this is a data node or a container
     */
    protected $internalIsContainer = false;

    /**
     * @var string default validation message string
     */
    protected $internalValidationMessage = "option not in list";

    /**
     * @var bool field may contain multiple servers at once
     */
    private $internalMultiSelect = false;

    /**
     * @var string default description for empty item
     */
    private $internalEmptyDescription = "none";

    /**
     * @var array valid options for this list
     */
    private $internalOptionList = array();


    /**
     * @var null source field
     */
    private $internalSourceField = null;

    /**
     * @var null source file pattern
     */
    private $internalSourceFile = null;

    /**
     * @var bool automatically select all when none is selected
     */
    private $internalSelectAll = false;

    /**
     * set descriptive text for empty value
     * @param string $value description
     */
    public function setBlankDesc($value)
    {
        $this->internalEmptyDescription = $value;
    }

    /**
     * @param string $value source field, pattern for source file
     */
    public function setSourceField($value)
    {
        $this->internalSourceField = basename($this->internalParentNode->$value);
    }

    /**
     * @param string $value optionlist content to use
     */
    public function setSourceFile($value)
    {
        $this->internalSourceFile = $value;
    }

    /**
     * @param string $value automatically select all when none is selected
     */
    public function setSelectAll($value)
    {
        if (strtoupper(trim($value)) == 'Y') {
            $this->internalSelectAll = true;
        } else {
            $this->internalSelectAll = false;
        }
    }

    /**
     * populate selection data
     */
    protected function actionPostLoadingEvent()
    {
        if ($this->internalSourceField != null && $this->internalSourceFile != null) {
            $sourcefile = sprintf($this->internalSourceFile, $this->internalSourceField);
            if (is_file($sourcefile)) {
                $data = json_decode(file_get_contents($sourcefile), true);
                if ($data != null) {
                    $this->internalOptionList = $data;
                    if ($this->internalSelectAll && $this->internalValue == "") {
                        $this->internalValue = implode(',', array_keys($this->internalOptionList));
                    }
                }
            }
        }
    }

    /**
     * select if multiple authentication servers may be selected at once
     * @param $value boolean value Y/N
     */
    public function setMultiple($value)
    {
        if (trim(strtoupper($value)) == "Y") {
            $this->internalMultiSelect = true;
        } else {
            $this->internalMultiSelect = false;
        }
    }

    /**
     * get valid options, descriptions and selected value
     * @return array
     */
    public function getNodeData()
    {
        $result = array ();
        // if relation is not required and single, add empty option
        if (!$this->internalIsRequired && !$this->internalMultiSelect) {
            $result[""] = array("value"=>$this->internalEmptyDescription, "selected" => 0);
        }

        $options = explode(',', $this->internalValue);
        foreach ($this->internalOptionList as $optKey => $optValue) {
            if (in_array($optKey, $options)) {
                $selected = 1;
            } else {
                $selected = 0;
            }
            $result[$optKey] = array("value"=>$optValue, "selected" => $selected);
        }
        // sort keys
        ksort($result);
        return $result;
    }


    /**
     * retrieve field validators for this field type
     * @return array returns InclusionIn validator
     */
    public function getValidators()
    {
        $validators = parent::getValidators();
        if ($this->internalValue != null) {
            if ($this->internalMultiSelect) {
                // field may contain more than one value
                $validators[] = new CsvListValidator(array('message' => $this->internalValidationMessage,
                    'domain'=>array_keys($this->internalOptionList)));
            } else {
                // single value selection
                $validators[] = new InclusionIn(array('message' => $this->internalValidationMessage,
                    'domain'=>array_keys($this->internalOptionList)));
            }
        }
        return $validators;
    }
}
