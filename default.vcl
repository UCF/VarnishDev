vcl 4.0;


import std;        
import directors;  


# Default backend definition. Set this to point to your content server.
backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

sub vcl_backend_response {
  
  // If it's a POST, hit_for_pass.
    if (bereq.method == "POST")
    {
        set beresp.uncacheable = true;
        set beresp.ttl = 120s;
        return (deliver);
    }

    // If backend says don't cache, don't cache, man. Respect.
    if ( beresp.http.Cache-Control ~ "private")
    {
        set beresp.uncacheable = true;
        set beresp.ttl = 120s;
        return (deliver);
    }

    // If it's an /administrator url, don't cache it.
    if ( bereq.url ~ "^/administrator" )
    {
        set beresp.uncacheable = true;
        set beresp.ttl = 120s;
        return (deliver);
    }

    // Don't cache the login page, 'cause we always want to send the (new) proper session cookie when a user wants to login.
    // A user must have a valid session cookie before authenticating, so when they receive the login page, they should also 
    // receive the set-cookie directive with their (valid) session id.
    if ( bereq.url ~ "^/login" )
    {
        set beresp.uncacheable = true;
        set beresp.ttl = 120s;
        return (deliver);
    }

    // only cache responses that are HTTP 200 or 404s
    // Pass on caching objects whose response is not 200 and not 404.
    if ( beresp.status != 200 && beresp.status != 404 )
    {
        set beresp.uncacheable = true;
        set beresp.ttl = 120s;
        return (deliver);
    }

// Allow items to be stale if required.
    set beresp.grace = 1h;


 // unset the etag header.
    unset beresp.http.etag;
  

  if (beresp.status >= 500 && beresp.status < 600) {
        unset beresp.http.Cache-Control;
        set beresp.http.Cache-Control = "no-cache, max-age=0, must-revalidate";
        set beresp.ttl = 0s;
        set beresp.http.Pragma = "no-cache";
        set beresp.uncacheable = true;
        return(deliver);
    }
 
 // cache content for 1 hour. Feel free to change this number for however long you wish Varnish to cache content for.
    // Logged in users only get cached for 2 minutes.
    if (bereq.http.cookie ~ "loggedin" )
    {
        set beresp.ttl = 2m;
    } else {
        set beresp.ttl = 60m;
    }

 #ban code:
  set beresp.http.x-url = bereq.url;

// Deliver us from cache.    
return (deliver);
}

sub vcl_deliver {

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



    

    // set and/or append X-Forwarded-For header.
    if (req.restarts == 0) {
        if (req.http.X-Forwarded-For) {
            set req.http.X-Forwarded-For =
            req.http.X-Forwarded-For + ", " + client.ip;
        } else {
            set req.http.X-Forwarded-For = client.ip;
        }
    }


    # allow PURGE from localhost and ....
    if (req.method == "PURGE") {
                if (!client.ip ~ purge) {
                        #return(synth(405,"Not allowed.")); #This is used for regular purges
			return(synth(403, "Not allowed")); #This is used for ban luker friendly bans
                }
		ban("obj.http.x-url ~ " + req.url); #This is used for ban luker friendly bans
                #return (purge);  #used for regular purges
        }

    #If given an uppercase "BAN" command we will ban specific objects
    if(req.method == "BAN"){
	#Same ACL check as above:
	if(!client.ip ~ purge) {
		return(synth(403, "Not allowed."));
	}
	
	#This is how a general ban works:
        ban("req.http.host == " + req.http.host +
                " && req.url == " + req.url);

        # Throw a synthetic page so the
        # request won't go to the backend.
        return(synth(200, "Ban added"));
	}

    #If given a lowercase "ban" command we will ban the entire host's domain	
    if(req.method == "ban"){
        #Same ACL check as above:
        if(!client.ip ~ purge) {
                return(synth(403, "Not allowed."));
        }

        #This should ban the entire domain of a host:
        ban("req.http.host ~ " + req.http.host);

        # Throw a synthetic page so the
        # request won't go to the backend.
        return(synth(200, "Ban added"));

    }
    
   
   
   if (req.method != "GET" &&
      req.method != "HEAD" &&
      req.method != "PUT" &&
      req.method != "POST" &&
      req.method != "TRACE" &&
      req.method != "OPTIONS" &&
            req.method != "DELETE") {
        /* Non-RFC2616 or CONNECT which is weird. */
       return (pipe);
    }
    if (req.method != "GET" && req.method != "HEAD") {
        /* We only deal with GET and HEAD by default */
        return (pass);
    } 

     // If it's a POST request, or part of component/banners, pass to backend.
    if(req.url ~ "^/component/banners" || req.method == "POST")
    {
        return (pass);
    }

    // If your login page is not at "/login", change the below line. This statement is primarily so a user will get a unique
    // session cookie if they visit the administrator section, or the login section. You can't log into Joomla without having 
    // a valid session cookie to begin with.
    if (req.url ~ "^/login" || req.url ~ "^/administrator")
    {
        return (pass);
    }

   #if (req.http.Upgrade ~ "(?i)websocket") {
   #     return (pipe);
   #}
	
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
    
    #SSL HASHING
     // If it's not a static resource, include X-Forwarded-Proto in the hash (if it exists).
    // This makes dynamic content unique for ssl vs non-ssl
    if (!(req.url ~ "^[^?]*\.(bmp|bz2|css|doc|eot|flv|gif|gz|ico|jpeg|jpg|js|less|mp[34]|pdf|png|rar|rtf|swf|tar|tgz|txt|wav|woff|xml|zip)(\?.*)?$"))
    {
    	if (req.http.X-Forwarded-Proto ~ "https") {
		hash_data(req.http.X-Forwarded-Proto);
    	}
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

sub vcl_pipe {
     if (req.http.upgrade) {
         set bereq.http.upgrade = req.http.upgrade;
     }
}

#########################################################
#End of Functions                                       #
#########################################################
