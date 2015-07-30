#!/usr/bin/perl
#
# Script for auto testing controllers, check each controller by curl with
# data given by config.
#
# Require libraries: libwww-curl-perl, libxml-dom-perl
#
# @author Ondřej Fibich
# @version 1.1
#

use strict;
use warnings;
use XML::DOM;
use URI::Escape;

# Print error message to stderr and terminate program with error code
# Params:
#	$message	Error message to STDERR
#	$ecode		Error code for exit
#
sub Error($$);

# Print warning message to stderr
# Params:
#	$message	Warning message to STDERR
#
sub Warning($);

# XML config for unit testing
my $XML_CONFIG	= "unit_testing_config.xml";
# Tag name of controller in XML config
my $TAG_NAME	= "controller";
# XML Parser
my $parser		= new XML::DOM::Parser;
# Error counter
my $error_counter = 0;
# Check valid counter
my $valid_counter = 0;

if ($#ARGV != 1 and $#ARGV != 2)
{
	Error("Wrong arguments, missing url or cookies file", 1);
}

# Already created config?
# Is readable?
if (!(-r $XML_CONFIG))
{
	Error("Cannot read from file: " . $XML_CONFIG, 2);
}

# Read
my $xml_reader = $parser->parsefile($XML_CONFIG);

if (!$xml_reader)
{
	Error("Cannot parse config file", 2);
}

# Go throught config
my $readed_items = $xml_reader->getElementsByTagName($TAG_NAME);
# All items
for (my $i = 0; $i < $readed_items->getLength; $i++)
{
	my $readed_item = $readed_items->item($i);
	my $readed_methods = $readed_item->getElementsByTagName("method");
	my $c_name = $readed_item->getAttributeNode("name")->getValue;
	# All methods of current controller
	for (my $u = 0; $u < $readed_methods->getLength; $u++)
	{
		my $readed_method = $readed_methods->item($u);
		my $m_name = $readed_method->getAttributeNode("name")->getValue;
		my $readed_inputs = $readed_method->getElementsByTagName("input");
		# Get all inputs for this controller
		for (my $w = 0; $w < $readed_inputs->getLength; $w++)
		{
			my $readed_input = $readed_inputs->item($w);
			my $readed_params = $readed_input->getElementsByTagName("param");
			# init transfer
			my $url = $ARGV[0] . "/en/" . $c_name . "/" . $m_name;
			my $get = "";
			my $data = "";
			# Get all params of input
			for (my $v = 0; $v < $readed_params->getLength; $v++)
			{
				my $readed_param = $readed_params->item($v);
				my $param_val = $readed_param->getAttributeNode("value")->getValue;
				my $type_attr = $readed_param->getAttributeNode("type");
				
				if (defined $type_attr)
				{
					if (lc($type_attr->getValue) eq "post")
					{
						$data .= "&" if ($data ne "");
						$data .= uri_escape($readed_param->getAttributeNode("name")->getValue);
						$data .= "=" . uri_escape($param_val);
					}
					elsif (lc($type_attr->getValue) eq "get")
					{
						$get .= ($get ne "") ? "&" : "?";
						$get .= uri_escape($readed_param->getAttributeNode("name")->getValue);
						$get .= "=" . uri_escape($param_val);
					}
				}
				else
				{
					$url .= "/" . $param_val;
				}
			}

			# Add get variables
			$url .= $get;

			my $index = ($error_counter + $valid_counter);
			my $filename = "curl/controller_test_error_" . $index . ".html";
			my $http_code;
			# download page by curl
			if ($data eq "")
			{
				$http_code = `curl -s -L -w %{http_code} -o "${filename}" --cookie "${ARGV[1]}" "${url}"`;
			}
			else
			{
				$http_code = `curl -s -L -w %{http_code} -o "${filename}" --data "${data}" --cookie "${ARGV[1]}" "${url}"`;
			}

			if ($http_code != 200)
			{
				$error_counter = $error_counter + 1;
				print STDERR "$index:$url";
				print STDERR " POST: $data" if ($data ne "");
				print STDERR "\n";
			}
			else
			{
				$valid_counter = $valid_counter + 1;

				# benchmark?
				if ($#ARGV == 2 and open(my $file, $filename))
				{
					my $fcontent = join("", <$file>);

					if ($fcontent =~ /Loaded in (\d+\.\d+) seconds, using (\d+\.\d+)MB of memory. Version:/)
					{
						`echo "$1\t$2\t$url\t$data" >> curl/cstats`;
					}

					close($file);
				}

				unlink($filename);
			}
		}
	}
}

$xml_reader->dispose;

# output
print $error_counter . ":" . $valid_counter . "\n";

exit 0;

################################################################################


# Print error message to stderr and terminate program with error code
# Params:
#	$message	Error message to STDERR
#	$ecode		Error code for exit
#
sub Error($$)
{
	my($message, $ecode) = @_;
	print STDERR "Program error: ";
	print STDERR $message . "\n";
	print STDERR "Program will be terminated...\n";
	exit $ecode;
}

# Print warning message to stderr
# Params:
#	$message	Warning message to STDERR
#
sub Warning($)
{
	my($message) = @_;
	print STDERR "Program warning: ";
	print STDERR $message . "\n";
}
