<?php

require_once(__DIR__ . '/main.php');

$canonical_url = filter_input_survey_url(INPUT_GET, 's');
$canonical = get_canonical($canonical_url);
$survey = get_survey_by_id($canonical);
$fields = array();
if ($survey) {
    foreach ($survey['fields'] as $field_id) {
        $fields[$field_id] = get_field($field_id);
    }

    $render_fields = array(
        'label' => '<label for="%1$s">%2$s</label>' . "\r\n",
        'input' => '<input type="text" name="%1$s" id="%1$s"/>' . "\r\n",
        'checkbox' => '<label for="%1$s">%3$s</label><input type="checkbox" name="%1$s" value="%2$s"/>' . "\r\n",
        'dropdown' => '<select name="%1$s"/>%2$s</select>' . "\r\n",
        'dropdown_option' => '<option value="%1$s"/>%2$s</option>' . "\r\n",
    );

    $errors = array();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tosubmit = array();
        foreach ($fields as $field) {
            $userdata = isset($_POST[$field['id']]) ? filter_var($_POST[$field['id']]) : '' ;

            if ($field['required'] && !$userdata) {
                $errors[$field['id']] = sprintf('%1$s can not be empty.', $field['name']);
            }
            $submission = array($survey['id'], $canonical['id'], $field['id'], $field['name'], $userdata);
            array_push($tosubmit, $submission);
        }

        if (empty($errors)) {
            foreach ($tosubmit as $submission) {
                create_submission(...$submission);
            }
        }
    }

    if (isset($_GET['s']) && $survey && !empty($fields)) {
        ob_start();
        printf('<form method="POST">');
        foreach ($fields as $field) {
            printf($render_fields['label'], $field['id'], $field['label']);
            if (array_key_exists($field['id'], $errors)) {
                printf('<p class="ierror">%1$s</p>', $errors[$field['id']]);
            }
            if ($field['type'] === 'dropdown') {
                $options = '';
                foreach ($field['options'] as $key => $value) {
                    $options .= sprintf($render_fields['dropdown_option'], $value, $value);
                }
                printf($render_fields['dropdown'], $field['id'], $options);
            }
            if ($field['type'] === 'input') {
                printf($render_fields['input'], $field['id']);
            }
            if ($field['type'] === 'checkbox') {
                foreach ($field['options'] as $key => $value) {
                    printf($render_fields['checkbox'], $field['id'], $value, $value);
                }
            }
        }
        printf('<div><button type="submit">Submit</button></div>');
        csrf_token();
        printf('</form>');
        _e(ob_get_clean(), array('no_menu' => true));
        _end();
    }
}
_e("Hello!", array(
    'title' => 'Main Page',
));

_e("Hello2");

_end();
