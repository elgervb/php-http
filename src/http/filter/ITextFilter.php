<?php
namespace http\filter;

interface ITextFilter
{

    /**
     * Filters a string
     *
     * @param $aString string
     *            The string to be filtered
     *            
     * @return String The filtered string
     */
    public function filter($aString);
}