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
        'email' => '<input type="text" name="%1$s" id="%1$s"/>' . "\r\n",
        'checkbox' => '<label for="%1$s">%3$s</label><input type="checkbox" name="%1$s" value="%2$s"/>' . "\r\n",
        'dropdown' => '<select name="%1$s"/>%2$s</select>' . "\r\n",
        'dropdown_option' => '<option value="%1$s"/>%2$s</option>' . "\r\n",
    );

    $filters = array(
        'input' => FILTER_DEFAULT,
        'checkbox' => FILTER_DEFAULT,
        'dropdown' => FILTER_DEFAULT,
        'email' => FILTER_VALIDATE_EMAIL
    );

    $errors = array();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tosubmit = array();
        foreach ($fields as $field) {
            $userdata = isset($_POST[$field['id']]) ? filter_var($_POST[$field['id']], $filters[$field['type']]) : '' ;

            if ($field['required'] && !$userdata) {
                $errors[$field['id']] = sprintf('please enter a valid %1$s', $field['name']);
            }
            if ($field['is_unique']) {
                $submissions = get_survey_data($survey['id'], $field['id'], $userdata);
                if (!empty($submissions)) {
                    $errors[$field['id']] = sprintf('value already used and only allowed once.', $userdata);
                }
            }
            $submission = array($field['id'], $field['name'], $userdata);
            array_push($tosubmit, $submission);
        }

        if (empty($errors)) {
            $submission_group = create_submission_group($survey['id'], $canonical['id']);
            $field_error = 0;
            if ($submission_group) {
                foreach ($tosubmit as $submission) {
                    $id_submission_data = create_submission_data($submission_group, ...$submission);
                    if (!$id_submission_data) {
                        $field_error++;
                    }
                }
            }

            if ($field_error) {
                _e('We\'re encountering an error, please try again.');
            } else {
                _e($survey['post_message'], array('no_menu' => 'true'));
                _end();
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
            if ($field['type'] === 'email') {
                printf($render_fields['email'], $field['id']);
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
