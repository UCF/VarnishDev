#
# This is an example VCL file for Varnish.
#
# It does not do anything by default, delegating control to the
# builtin VCL. The builtin VCL is called when there is no explicit
# return statement.
#
# See the VCL chapters in the Users Guide at https://www.varnish-cache.org/docs/
# and http://varnish-cache.org/trac/wiki/VCLExamples for more examples.

####################################################################
#SAMPLE DEFAULT VCL FOR VARNISH VERSION 3 SUPORTING BANS AND PURGES#
####################################################################

# Default backend definition. Set this to point to your content server.
backend default {
    .host = "127.0.0.1";
    .port = "8080";
}


sub vcl_fetch {
 	 set beresp.http.x-url = req.url;
	return (deliver);
}

sub vcl_deliver {
     	#Happens when we have all the pieces we need, and are about to send the
     	#response to the client.
     
     	#You can do accounting or modifying the final object here.

	if (obj.hits > 0) {
                set resp.http.X-Cache = "HIT";
        } else {
                set resp.http.X-Cache = "MISS";
        }

	unset resp.http.x-url; # Optional
	return (deliver);
}

#########################################################
#The Following functions will allow for Bans and Purges:#
#########################################################

acl purge {
        "localhost";
        "10.192.4.105"/24;
}

sub vcl_recv {
   	
	 if (req.restarts == 0) {
                if (req.http.x-forwarded-for) {
                        set req.http.X-Forwarded-For =
                        req.http.X-Forwarded-For + ", " + client.ip;
                } else {
                set req.http.X-Forwarded-For = client.ip;
                }
        }
	

	# allow PURGE from localhost and ....
   
	if (req.request == "PURGE") {
                if (!client.ip ~ purge) {
                        error 405 "Not allowed.";
                }
                return (lookup);
        }
    
	if (req.request == "BAN") {
                # Same ACL check as above:
                if (!client.ip ~ purge) {
                        error 405 "Not allowed.";
                }
                ban("req.http.host == " + req.http.host +
                      " && req.url == " + req.url);

                # Throw a synthetic page so the
                # request won't go to the backend.
                error 200 "Ban added";
    	}
 	
    	if (req.request != "GET" &&
      		req.request != "HEAD" &&
     		 req.request != "PUT" &&
     		 req.request != "POST" &&
     		 req.request != "TRACE" &&
      		req.request != "OPTIONS" &&
      		req.request != "DELETE") {
        	/* Non-RFC2616 or CONNECT which is weird. */
        	return (pipe);
    	}
    	if (req.request != "GET" && req.request != "HEAD") {
        	/* We only deal with GET and HEAD by default */
        	return (pass);
    	}
	return (lookup);    
}

sub vcl_pipe {
    
   # Allows for websocket support
    if (req.http.upgrade) {
        set bereq.http.upgrade = req.http.upgrade;
    }
    return (pipe);
}


sub vcl_pass {
    return (pass);
}

sub vcl_hash {
    hash_data(req.url);
    if (req.http.host) {
        hash_data(req.http.host);
    } else {
        hash_data(server.ip);
    }
    return (hash);
}

sub vcl_hit {
        if (req.request == "PURGE") {
                purge;
                error 200 "Purged.";
        }
}

sub vcl_miss {
        if (req.request == "PURGE") {
                purge;
                error 200 "Purged.";
        }
}

sub vcl_error {
	set obj.http.Content-Type = "text/html; charset=utf-8";
    	set obj.http.Retry-After = "5";
    	synthetic {"
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
  <head>
    <title>"} + obj.status + " " + obj.response + {"</title>
  </head>
  <body>
    <h1>Error "} + obj.status + " " + obj.response + {"</h1>
    <p>"} + obj.response + {"</p>
    <h3>Guru Meditation:</h3>
    <p>XID: "} + req.xid + {"</p>
    <hr>
    <p>Varnish cache server</p>
  </body>
</html>
"};
    return (deliver);
}

sub vcl_init {
        return (ok);
}

sub vcl_fini {
        return (ok);
}


#########################################################
#End of Functions                                       #
#########################################################
