<?php

namespace Tests\Fixtures;

class PropertyDocumentationTestClass
{
    /**
     * Property documentation
     */
    public $withoutType;

    /**
     * Property documentation
     * @type int
     */
    public $withType;

    public $noDoc;
}
