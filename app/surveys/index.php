<?php

require('../..' . '/main.php');

if ($_SESSION['level'] < 1) {
    _e("Operation not allowed;");
    _end();
}

$errors = array(
    'survey_name' => '',
    'survey_description' => '',
    'survey_url' => '',
    'survey_status' => ''
);

$surveys = get_surveys();
ob_start();

printf('<table>');
printf('<tr>');
printf('<th>Survey Name</th>');
printf('<th>Survey Description</th>');
printf('<th>Survey Url</th>');
printf('<th>Survey Status</th>');
printf('<th>Options</th>');
printf('</tr>');

$select_options = '<option value=0>Disabled</a>
<option value=1>Enabled</a>';
printf('<tr>');
printf('<td>%1$s<input type="text" name="survey_name"/></td>', $errors['survey_name']);
printf('<td>%1$s<input type="text" name="survey_description"/></td>', $errors['survey_description']);
printf('<td>%1$s<input type="text" name="survey_url"/></td>', $errors['survey_url']);
printf('<td>%1$s<select name="survey_status">%2$s</select>', $errors['survey_status'], $select_options);
printf('<td><button type="submit">Create survey</button></td>');
printf('</tr>');

$status = array('Disabled', 'Enabled');

foreach ($surveys as $survey) {
    $url = PROGPATH . '/?s=' . $survey['url'];
    $edit = PROGPATH . '/app/surveys/edit?i=' . $survey['id'];
    printf('<tr>');
    printf('<td>%1$s</td>', $survey['name']);
    printf('<td>%1$s</td>', $survey['description']);
    printf('<td><a href="%1$s">%2$s</a></td>', $url, $survey['url']);
    printf('<td>%1$s</td>', $status[$survey['status']]);
    printf('<td><a href="%1$s">Edit</a></td>', $edit);
    printf('</tr>');
}

_e(ob_get_clean());
_end();
