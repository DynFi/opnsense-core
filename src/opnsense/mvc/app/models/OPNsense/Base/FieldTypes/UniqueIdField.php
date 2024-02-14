<?php

/*
 * Copyright (C) 2016-2023 Deciso B.V.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace OPNsense\Base\FieldTypes;

use Phalcon\Filter\Validation\Validator\InclusionIn;

/**
 * Class UniqueIdField
 * @package OPNsense\Base\FieldTypes
 */
class UniqueIdField extends BaseField
{
    /**
     * @var bool marks if this is a data node or a container
     */
    protected $internalIsContainer = false;

    /**
     * @var bool past actionPostLoadingEvent? (load unique id from disk, generate new when not)
     */
    private $fieldLoaded = false;

    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        if (empty((string)$this) && $this->fieldLoaded) {
            parent::setValue(uniqid('', true));
        } elseif (empty((string)$this) && !$this->fieldLoaded) {
            parent::setValue($value);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function actionPostLoadingEvent()
    {
        parent::actionPostLoadingEvent();
        $this->fieldLoaded = true;
    }

    /**
     * {@inheritdoc}
     */
    public function applyDefault()
    {
        /** When cloned (add), set our default to a new uniqueid */
        $this->fieldLoaded = true;
        $this->setValue(null);
    }
}
