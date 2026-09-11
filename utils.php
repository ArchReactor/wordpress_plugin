<?php
class ArchReactorUtils {
    public static function renderTable($values, $template, $header, $css_id, $css_class='') {
        $header_html = "";
        $tbody_html  = "";
        if($header){
            foreach ($header as $key => $value) {
                $header_html .= "<th>{$value}</th>";
            }
        }
        foreach ($values as $record) {
            $row_html = "";
            if ($template) {
                // use template
                $row_html = preg_replace_callback('/\{\$([^{}]+)\}/m', function ($matches) use ($record) {
                    return htmlentities($record[$matches[1]]);
                }, $template);
            } else {
                foreach ($record as $key => $value) {
                    if($key === 'title') {
                        $row_html .= "<th>" . $value . "</th>";	
                    } else {
                        $row_html .= "<td>" . $value . "</td>";
                    }
                }
            }

            $tbody_html .= "<tr>$row_html</tr>";
        }
        $html = "<table id='$css_id' class='ux-cv-listing $css_class'><thead>$header_html</thead><tbody>$tbody_html</tbody></table>";

        return $html;
    }
}