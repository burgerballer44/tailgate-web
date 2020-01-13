<?php

namespace TailgateWeb\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FormBuilderExtension extends AbstractExtension
{
    private $parsedBody;
    private $fieldsToIgnore = [
        'password',
        'confirm_password',
    ];

    public function __construct($parsedBody)
    {
        $fieldsToIgnore = $this->fieldsToIgnore;

        if (!is_array($parsedBody)) {
            $parsedBody = [];
        }

        $parsedBodyWithIgnoredFieldsRemoved = array_filter($parsedBody, function($value) use ($fieldsToIgnore) {
            return !in_array($value, $fieldsToIgnore);
        }, ARRAY_FILTER_USE_KEY);

        $this->parsedBody = $parsedBodyWithIgnoredFieldsRemoved;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('textField', [$this, 'textField']),
            new TwigFunction('dropdownField', [$this, 'dropdownField']),
            new TwigFunction('submitButton', [$this, 'submitButton']),
            new TwigFunction('displayErrors', [$this, 'displayErrors']),
            new TwigFunction('tableStart', [$this, 'tableStart']),
            new TwigFunction('tableEnd', [$this, 'tableEnd']),
            new TwigFunction('tableHeader', [$this, 'tableHeader']),
            new TwigFunction('tableData', [$this, 'tableData']),
        ];
    }

    /**
     * [textField description]
     * @param  [type] $fieldName   [description]
     * @param  [type] $label       [description]
     * @param  string $type        [description]
     * @param  string $placeholder [description]
     * @param  string $required    [description]
     * @param  string $value       [description]
     * @return [type]              [description]
     */
    public function textField($fieldName, $label, $type = 'text', $placeholder = '', $required = '', $value = '')
    {
        $value = $this->parsedBody[$fieldName] ?? $value;
        $value = htmlspecialchars($value);

        $label = "<label class='block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2'>{$label}</label>";
        $input = "<input class='block border border-gray-300 w-full max-w-lg p-3 rounded mb-4' placeholder='{$placeholder}' type='{$type}' name={$fieldName} value='{$value}' {$required}>";

        return $label . $input;
    }

    /**
     * [dropdownField description]
     * @param  [type] $fieldName     [description]
     * @param  [type] $label         [description]
     * @param  [type] $selectedValue [description]
     * @param  array  $options       [description]
     * @param  string $placeholder   [description]
     * @param  string $disabled      [description]
     * @return [type]                [description]
     */
    public function dropdownField($fieldName, $label, $selectedValue, array $options, $placeholder = '', $disabled = 'disabled')
    {
        $selectedValue = $this->parsedBody[$fieldName] ?? $selectedValue;
        $selectedValue = htmlspecialchars($selectedValue);
        
        $output = "<label class='block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2'>{$label}</label><select required name='{$fieldName}' class='appearance-none block w-full border border-gray-300 max-w-lg rounded py-3 px-4 leading-tight focus:outline-none focus:border-gray-500 mb-4'>";

        $output .= "<option selected {$disabled} value='' >{$placeholder}...</option>";

        foreach ($options as $key => $value) {
            if ($key == $selectedValue) {
                $output .= "<option selected value='{$key}'>{$value}</option>";
            } else {
                $output .= "<option value='{$key}'>{$value}</option>";                                     
            } 
        }
        $output .= "</select>";

        return $output;
    }

    /**
     * [submitButton description]
     * @param  string $text [description]
     * @return [type]       [description]
     */
    public function submitButton($text = 'Go')
    {
        return "<button type='submit' class='button hover:bg-navy focus:outline-none'
        >{$text}</button>";
    }

    /**
     * [displayErrors description]
     * @param  [type] $field  [description]
     * @param  [type] $errors [description]
     * @return [type]         [description]
     */
    public function displayErrors($field, $errors)
    {
        $output = '';

        if (isset($errors[$field])) {
            $output .= "<div class='flex flex-col'>";
            foreach ($errors[$field] as $error) {
                $output .= "<p class='validation-error'>{$error}</p>";
            }
            $output .= "</div>";
        }

        return $output;
    }




    public function tableStart()
    {
        return '<div class="flex justify-start"><table class="text-md bg-white shadow-md rounded"><tbody>';
    }

    public function tableEnd()
    {
        return '</tbody></table></div>';
    }

    public function tableHeader($value)
    {
        $html = "<th class='p-1'>";
        $html .= $value;
        $html .= "</th>";

        return $html;
    }

    public function tableData($value)
    {
        $html = "<td class='p-1 py-2'>";
        $html .= $value;
        $html .= "</td>";

        return $html;
    }
}
