#!/usr/bin/env python
################################################################################
#
# Script for redirecting with HTTP 302 code for FreenetIS redirection
#
# version: 0.1.2
# author:  Ondrej Fibich <ondrej.fibich@gmail.com>
#
################################################################################

import datetime
import re
import signal
import sys
import socket
import thread

########## Classes of script ###################################################

##
# Class with utils and helpers
#
class Util(object):
	##
	# Returns current datetime (NOW) in string format
	#
    @staticmethod
    def now():
        return datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")

##
# Handles connections
#
class ConnectionHandler:

	##
	# Initialize socket and other required variables
	#
    def __init__(self, port, target_url):
        # patterns for retrieving of response
        self.pattern_http_header = re.compile("GET (.*) HTTP/")
        self.pattern_http_host = re.compile("Host: (.*)\r\n")
        self.pattern_url = re.compile("^(https?)://([\w-]+\.)+[\w-]+(/[\w -./?\%&=]*)?$")
        # variables
        self.target_url = target_url
        self.port = port
        self.listener = None
        self.on = False # inicator of running
    
    ##
    # Is on?
    #
    def is_on(self):
    	return self.on
    	
    ##
    # Open socket
    #
    def open(self):
    	if not self.listener:
    		self.listener = socket.socket(socket.AF_INET, socket.SOCK_STREAM) # server socket
        	self.listener.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1) # repeatly used
        	self.listener.bind(("", self.port)) # bind socket to port
        	self.listener.listen(25) # max connection in same time
        	self.on = True
        	# debug
        	print "[INFO] %s FreenetIS HTTP 302 Redirector is active (port: %d)\n" % (Util.now(), self.port)
    
    ##
    # Close socket
    #
    def close(self):
    	if self.listener:
        	self.listener.close()
    		print "[INFO] %s Closing socket" % Util.now()
    
    ##
    # Signal hadler
    #
    def signal_handler(self, signum, frame):
        print "[INFO] %s Catch signal %d." % (Util.now(), signum)
    	self.on = False
    
    ##
    # Thread for processing of request and for creating and sending of response.
    # @param conn Client connection
    #
    def _client_thread(self, conn):
        try:
        
        	# download header
        	request_header = conn.recv(1024)
        	
        	if not request_header:
        		request_header = "" # even if nothing received, redirect
        	
        	# header content variables
        	origin_host = ""
        	origin_path = ""
        	
        	# read header	
        	m_http_header = self.pattern_http_header.search(request_header)
        	
        	if m_http_header:
        		origin_path = m_http_header.group(1)
        	
        	m_origin_path = self.pattern_http_host.search(request_header)
        	
        	if m_origin_path:
        		origin_host = "http://" + m_origin_path.group(1).strip()
        	
        	# make URL
        	origin = origin_host + origin_path
        	
        	# debug
        	print "[INFO] %s Origin: %s" % (Util.now(), origin)
        	
        	# check readed data if wring set some other common url
        	if not self.pattern_url.match(origin):
        		origin = "http://www.google.com"
        		
        	# make redir URL
        	url = self.target_url + origin

        	# send our https redirect
        	conn.send("HTTP/1.1 302 Moved temporarily\r\n" +
        		"Location: " + url + "\r\n" +
        		"Connection: close\r\n" +
        		"Cache-control: private\r\n\r\n" +
        		"<html><body>Moved temporarily. Please go to <a href=\"" + url + "\">" + url + "</a> for this service.</body></html>\r\n\r\n")
        	
        	# debug
        	print "[INFO] %s Redirecting to %s" % (Util.now(), url)
        	
        finally:
        	# close connecting
        	conn.close()
        	# debug
        	print "[INFO] %s Closing connection.\n" % Util.now()
    
	##
	# Listens for incoming connection (every 1ms).
	# On new connection a 302 redirect is sended and then the connection is closed.
	#
    def run(self):
        # new connection?
        client_socket, client_addr = self.listener.accept()
        	
        # debug
        print "[INFO] %s Accepting connection from: %s:%d." % (Util.now(), client_addr[0], client_addr[1])
        
        # invoke thread
        thread.start_new_thread(self._client_thread, (client_socket,))

########## Working loop ########################################################

# load arguments
if len(sys.argv) != 3:
	print >> sys.stderr, "Wrong args count.. Terminating"
	sys.exit(1)

# port
try:
	port = int(sys.argv[1].strip())
except ValueError:
	print >> sys.stderr, "First argument has to be a port number.. Terminating"
	sys.exit(2)

# url for redirect
if not re.match("^(https?)://([\w-]+\.)+[\w-]+(/[\w -./?\%&=]*)?$", sys.argv[2].strip()):
	print >> sys.stderr, "Second argument has to be a URL.. Terminating"
	sys.exit(3)

target = sys.argv[2].strip().rstrip("/") + "/redirection/?redirect_to="

# init
connections = ConnectionHandler(port, target)

# connect
try:
	connections.open()
except socket.error, msg:
	connections.close()
	print >> sys.stderr, "Cannot create/bind socket, error (" + str(msg[0]) + "): " + str(msg[1])
	sys.exit(4)

# set signal handlers
signal.signal(signal.SIGINT, connections.signal_handler)
signal.signal(signal.SIGABRT, connections.signal_handler)

# endless loop for receiving of connections (do not stop even on error)
try:
	while connections.is_on():
		try:
			connections.run()
		except Exception as e: # on any error
			print >> sys.stderr, "[ERROR] %s An error occured: %s" % (Util.now(), e)
finally:
	# close connection
	connections.close()

