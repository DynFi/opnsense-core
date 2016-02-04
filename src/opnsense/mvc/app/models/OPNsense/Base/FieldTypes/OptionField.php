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

/**
 * Class OptionField
 * @package OPNsense\Base\FieldTypes
 */
class OptionField extends BaseField
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
     * @var string default description for empty item
     */
    private $internalEmptyDescription = "none";

    /**
     * @var array valid options for this list
     */
    private $internalOptionList = array();

    /**
     * set descriptive text for empty value
     * @param $value description
     */
    public function setBlankDesc($value)
    {
        $this->internalEmptyDescription = $value;
    }

    /**
     * setter for option values
     * @param $data
     */
    public function setOptionValues($data)
    {
        if (is_array($data)) {
            $this->internalOptionList = array();
            // copy options to internal structure, make sure we don't copy in array structures
            foreach ($data as $key => $value) {
                if (!is_array($value)) {
                    if ($key == "__empty__") {
                        $this->internalOptionList[""] = $value ;
                    } else {
                        $this->internalOptionList[$key] = $value ;
                    }
                }
            }
        }
    }

    /**
     * get valid options, descriptions and selected value
     * @return array
     */
    public function getNodeData()
    {
        $result = array ();
        // if relation is not required, add empty option
        if (!$this->internalIsRequired) {
            $result[""] = array("value"=>$this->internalEmptyDescription, "selected" => 0);
        }
        foreach ($this->internalOptionList as $optKey => $optValue) {
            if ($optKey == $this->internalValue) {
                $selected = 1;
            } else {
                $selected = 0;
            }
            $result[$optKey] = array("value"=>$optValue, "selected" => $selected);
        }

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
            $validators[] = new InclusionIn(array('message' => $this->internalValidationMessage,
                'domain'=>array_keys($this->internalOptionList)));
        }
        return $validators;
    }
}
