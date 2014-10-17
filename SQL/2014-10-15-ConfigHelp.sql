INSERT INTO help (parentID, hash, topic, content, created, updated) VALUES (-1, md5('configuration'), 'Configuration', 'The Configuration Module allows you to edit configuration settings from the front end. The configuration values are stored in the database, instead of in the config.xml file. Any settings that are not currently in the Configuration Module can still be found and edited from the config.xml file.\r\n\r\nTo edit any configuration settings, navigate to the field that you\'d like to edit in the module, click in the form area and edit/insert a value. Pressing enter while the edited form area is selected will save any changes that you make.\r\n\r\nYou should not edit more than two fields at a time as the submit function only saves the data in the form area that you have currently selected. Therefore, if you edit two different fields, only the one that is selected at the time you press enter will be saved. It is recommended to save any changes you make in one field by pressing enter, before moving on to other fields. This will prevent loosing any changes that you make.\r\n\r\nSome configuration settings can accept multiple values. For these settings, you can add additional fields by pressing the "Add Field". This will create an empty form area where you can insert new values. You must press enter while the new area is selected to save the new value. You can remove a field by pressing the remove button below the form area.\r\n\r\nCare should be taken when editing the fields as there is currently no way to revert changes.', '2014-09-01 00:00:00', NULL);