# Suvery Tool

please create a survey.json file
located one dir before public_html

the file should contain this structure:

PROGPATH should be the directory paths after the public_html folder 

{"PROGPATH":"\/survey",
"DBNAME":"survey",
"DBUSER":"root","
DBPASS":null,
"DBHOST":"127.0.0.1",
"DBPORT":null}

After the app is connected to the database,
you can login with 'admin@local.host' pw 'survey'
then you are forced to change the initial password;

a sample survey is auto-created;

new surveys can be created but I still have to create the
field editing/creation page, then any user-created field could be used in any survey.
