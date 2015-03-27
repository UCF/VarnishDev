#
# This is an example VCL file for Varnish.
#
# It does not do anything by default, delegating control to the
# builtin VCL. The builtin VCL is called when there is no explicit
# return statement.
#
# See the VCL chapters in the Users Guide at https://www.varnish-cache.org/docs/
# and http://varnish-cache.org/trac/wiki/VCLExamples for more examples.

# Marker to tell the VCL compiler that this VCL has been adapted to the
# new 4.0 format.
vcl 4.0;


######################
import std;
import directors;
######################

# Default backend definition. Set this to point to your content server.
backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

sub vcl_backend_response {
    # Happens after we have read the response headers from the backend.
     
    # Here you clean the response headers, removing silly Set-Cookie headers
    # and other mistakes your backend does.
  
  if (beresp.status >= 500 && beresp.status < 600) {
        unset beresp.http.Cache-Control;
        set beresp.http.Cache-Control = "no-cache, max-age=0, must-revalidate";
        set beresp.ttl = 0s;
        set beresp.http.Pragma = "no-cache";
        set beresp.uncacheable = true;
        return(deliver);
    }
 
 #ban code:
  set beresp.http.x-url = bereq.url;

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
	  
 	 #Ban code:
	unset resp.http.x-url; # Optional
}
#########################################################
#The Following functions will allow for Bans and Purges:#
#########################################################

acl purge {
        "localhost";
        "10.192.4.105";
}

sub vcl_recv {
    # allow PURGE from localhost and ....
    if (req.method == "PURGE") {
                if (!client.ip ~ purge) {
                        return(synth(405,"Not allowed."));
                }
                return (purge);
        }

  #Ban lurker friendly Purge:
  #  if (req.method == "PURGE") {
  #	if (client.ip !~ purge) {
  #    		return(synth(403, "Not allowed"));
  # 	}
  #	ban("obj.http.x-url ~ " + req.url); # Assumes req.url is a regex. This might be a bit too simple
  #	}
 

    
    if(req.method == "BAN"){
	#Same ACL check as above:
	if(!client.ip ~ purge) {
		return(synth(403, "Not allowed."));
	}
	ban("req.http.host == " + req.http.host +
                " && req.url == " + req.url);

        # Throw a synthetic page so the
        # request won't go to the backend.
        return(synth(200, "Ban added"));
	
    }

   # if (req.method != "GET" &&
   #   req.method != "HEAD" &&
   #   req.method != "PUT" &&
   #   req.method != "POST" &&
   #   req.method != "TRACE" &&
   #   req.method != "OPTIONS" &&
   #         req.method != "DELETE") {
   #     /* Non-RFC2616 or CONNECT which is weird. */
   #    return (pipe);
   # }
   # if (req.method != "GET" && req.method != "HEAD") {
   #     /* We only deal with GET and HEAD by default */
   #     return (pass);
   # } 
    return (hash);    
}

sub vcl_purge {
  # Only handle actual PURGE HTTP methods, everything else is discarded
  if (req.method != "PURGE") {
    # restart request
    set req.http.X-Purge = "Yes";
    return(restart);
  }
}

sub vcl_hash {
    hash_data(req.url);
    if (req.http.host) {
        hash_data(req.http.host);
    } else {
        hash_data(server.ip);
    }
    return (lookup);
}

sub vcl_synth {
    if (resp.status == 720) {
        # We use this special error status 720 to force redirects with 301 (permanent) redirects
        # To use this, call the following from anywhere in vcl_recv: error 720 "http://host/new.html"
        set resp.status = 301;
        set resp.http.Location = resp.reason;
        return (deliver);
    } elseif (resp.status == 721) {
        # And we use error status 721 to force redirects with a 302 (temporary) redirect
        # To use this, call the following from anywhere in vcl_recv: error 720 "http://host/new.html"
        set resp.status = 302;
        set resp.http.Location = resp.reason;
        return (deliver);
    }

    return (deliver);
}

sub vcl_synth {
    set resp.http.Content-Type = "text/html; charset=utf-8";
    set resp.http.Retry-After = "5";

    synthetic( {"
            <?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
            <html>
              <head>
                <title>"} + resp.status + " " + resp.reason + {"</title>
              </head>
              <body>
                <h1>Error "} + resp.status + " " + resp.reason + {"</h1>
                <p>"} + resp.reason + {"</p>
                <h3>Guru Meditation:</h3>
                <p>XID: "} + req.xid + {"</p>
                <hr>
                <p>Varnish cache server</p>
              </body>
            </html>
    "} );

    return (deliver);
}

#########################################################
#End of Functions                                       #
#########################################################
