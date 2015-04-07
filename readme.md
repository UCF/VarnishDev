Varnish Ban/Purger

This plugin was created in order to make it as easy as possible to issue bans and/or purges onto a varnish cache. It also attempts to automatically purge/ban new changes on your blog site. As of right now it is a work in progress.


Settings:
The plugin takes in a few settings from the user. 
-The IP address of the Varnish server.
-The port used to connect to varnish
-A URL which can be singled out for purging.
-Set the version of varnish you are using or want to use. 

Buttons:
-The Verify Varnish button checks that the settings allow for a connection to a varnish server. 
-The Purge URL button can be used to purge a single URL page which is specified in the URL setting.
-The Ban Blog button initiates a ban command which bans all pages on the host's domain. *Still testing to see if this works as intended*


Issues, bugs and missing functionality: 

* Settings must be saved before using the plugin. If you type in a new setting and then try to use the plugin it will revert the settings back to the last saved settings. *
* Right now the plugin only supports Varnish 4.0 commands, Varnish 3 support will be added later. *
* Can't use the varnish admin port through the plugin just yet *
* Some automatic purging is supported, it is not guranteed that everything is being automatically purged *
* The Verify Varnish function seems to be able to connect to varnish servers sometimes when incorrect settings are entered *
