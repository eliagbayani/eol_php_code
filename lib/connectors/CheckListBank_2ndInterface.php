<?php
namespace php_active_record;
/* */
class CheckListBank_2ndInterface
{
    function __construct()
    {
    }
    function go_2ndInterface($params)
    {
        // $included_fields = array("reference_author", "title", "publication_name", "actual_pub_date", "listed_pub_date", "publisher", "pub_place", "pages", "isbn", "issn", "pub_comment");
        // $references = self::get_text_contents('References');    
        // $fields = explode("\t", $references[0]);
        print_r($params);
    }
}
?>