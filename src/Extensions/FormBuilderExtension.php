<?php

namespace TailgateWeb\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FormBuilderExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('textField', [$this, 'textField']),
        ];
    }

    public function textField($fieldName, $label, $value, $type = 'text', $placeholder = '')
    {
        $value = htmlspecialchars($value);
        $output = "<label class='block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2'>{$label}</label><input class='appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500' placeholder='{$placeholder}' autocomplete='off' type='{$type}' name={$fieldName} value='{$value}'>";

        return $output;
    }

    public function displayErrors($key, $field, $errors)
    {
        $output = '';

        if (isset($errors[$key][$field])) {
            $output .= "<div>";
            foreach ($errors[$key][$field] as $error) {
                $output .= "<p class='text-red-500 text-xs italic'>" . $error . ".</p>";
            }
            $output .= "</div>";
        }

        return $output;
    }

    public function createDropdown($fieldName, $label, $selectedValue, $placeholder, array $options, $disabled = 'disabled')
    {
        $output = "<label class='block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2'>{$label}</label><select name='{$fieldName}' class='appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500'>";



        if (!$selectedValue) {
            $output .= "<option selected {$disabled} value='' >{$placeholder}...</option>";
        }

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

    public function createTextField($fieldName, $label, $value, $type = 'text', $placeholder = '')
    {
        $value = htmlspecialchars($value);
        $output = "<label class='block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2'>{$label}</label><input class='appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500' placeholder='{$placeholder}' autocomplete='off' type='{$type}' name={$fieldName} value='{$value}'>";

        return $output;
    }

    public function createRadioButtons($fieldName, $label, $selectedValue, array $options)
    {
        $output = "<label class='block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2'>{$label}</label>";

        foreach ($options as $key => $value) {
            $cleanValue = str_replace(" ", "", $value);
            $output .= " <input type='radio' id='{$fieldName}-{$cleanValue}' name='{$fieldName}' value='{$key}'";

            if ($key == $selectedValue) {
                $output .= " checked ";
            }
            $output .= " ><label for='{$fieldName}-{$cleanValue}'> {$value}</label> ";
        }

        return $output;
    }

    public function createCheckboxInputs($fieldName, $label, $selectedValues, array $options)
    {
        if (!is_array($selectedValues)) {
            $selectedValues = [];
        }

        $output = "<label class='block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2'>{$label}</label>";

        foreach ($options as $key => $value) {

            $cleanValue = str_replace(" ", "", $value);
            $output .= "<input type='checkbox' id='{$fieldName}-{$cleanValue}' name='{$fieldName}' value='{$key}'";

            foreach ($selectedValues as $selectedValue) {
                if ($key == $selectedValue) {
                    $output .= " checked ";
                }
            }

            $output .= " ><label for='{$fieldName}-{$cleanValue}'> {$value}</label> <br> ";
        }

        return $output;
    }

    public function makeTableHeader($searchModel, $field, $title)
    {
        $html = "<th class='p-1 whitespace-no-wrap'>";
        $html .= "<a href='index.php?{$searchModel->getQueryStringText()}";

        if ($field == $searchModel->getSortField()) {
            $html .= "&sortDirection={$searchModel->getOppositeSortDirection()}";
        } else {
            $html .= "&sortDirection={$searchModel->getSortDirection()}";
        }

        $html .= "&sortField={$field}'>";

        $html .= "<span>{$title}</span>";

        if ($field == $searchModel->getSortField()) {
                $html .= " <i class='fa {$searchModel->getSortDirectionIcon()}'></i>";
        } else {
            $html .= " <i class='fa {$searchModel->getDefaultSortDirectionIcon()}'></i>";
        }
        $html .= "</a></th>";

        return $html;
    }


}
