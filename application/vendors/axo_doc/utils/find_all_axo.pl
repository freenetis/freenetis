#!/usr/bin/perl
#
# Script for generating all AXO values in a form of a CSV file on stdin
# with columns:
#
# - AXO section		string
# - AXO value		string
# - is_own			boolean
#
# Info log is written to stderr.
#
# Takes only one option (-d) that specifies whether the output list should
# have unique values (no unique values by default).
#
# @author Ondřej Fibich
# @version 1.0
#

use strict;
use warnings;
use XML::Writer;
use XML::DOM;
use IO::File;
use File::Find;

# Base app dir
my $APP_DIR 		= "../../..";
# Model dir
my $MODEL_DIR 		= $APP_DIR . "/models";
# Controller dir
my $CONROLLER_DIR	= $APP_DIR . "/controllers";
# Helper dir
my $HELPER_DIR		= $APP_DIR . "/helpers";
# Libraries dir
my $LIBRARY_DIR		= $APP_DIR . "/libraries";
# Views dir
my $VIEW_DIR		= $APP_DIR . "/views";
# Ignore duplicates
my $unique			= 0;
# Array of printed AXOs
my %axos			= ();

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

# Parse controllers, models and helpers dir and write its content to XML config
# Params:
#   $tag        Tag name for item
#   $subject    Name of item (example: Model), used for error messages
#   $dir        Dir of items
# 
# Return:
#   Return list of counters, first item is count of parsed files,
#   second is count of parsed methods.
#
sub Parse($$$);

# command line options
if ($#ARGV >= 0)
{
	if ($ARGV[0] eq "-d")
	{
		$unique = 1;
	}
	else
	{
		print STDERR "usage: perl find_all_axo.pl [-d]\n";
		exit(1);
	}
}

# Print header for CSV
print "\"Action\";\"AXO section\";\"AXO value\";\"Is own\";\n";

# Parse controllers
print STDERR "PARSING CONTROLLERS\n";
print STDERR "=====================================================================\n";
my ($c_count, $m_count) = Parse("controller", "Controllers", $CONROLLER_DIR);
print STDERR $c_count . " controllers parsed, " . $m_count . " ACL call founded.\n\n";
# Parse models
print STDERR "PARSING MODELS\n";
print STDERR "=====================================================================\n";
($c_count, $m_count) = Parse("model", "Model", $MODEL_DIR);
print STDERR $c_count . " models parsed, " . $m_count . " ACL call founded.\n\n";
# Parse helpers
print STDERR "PARSING HELPERS\n";
print STDERR "=====================================================================\n";
($c_count, $m_count) = Parse("helper", "Helper", $HELPER_DIR);
print STDERR $c_count . " helpers parsed, " . $m_count . " ACL call founded.\n\n";
# Parse libraries
print STDERR "PARSING LIBRARIES\n";
print STDERR "=====================================================================\n";
($c_count, $m_count) = Parse("library", "Library", $LIBRARY_DIR);
print STDERR $c_count . " libraries parsed, " . $m_count . " ACL call founded.\n\n";
# Parse views
print STDERR "PARSING VIEWS\n";
print STDERR "=====================================================================\n";
($c_count, $m_count) = Parse("view", "View", $VIEW_DIR);
print STDERR $c_count . " views parsed, " . $m_count . " ACL call founded.\n\n";

# Exit script
exit(0);

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
	
# Parse controllers, models and helpers dir and write its content to XML config
# Params:
#   $tag        Tag name for item
#   $subject    Name of item (example: Model), used for error messages
#   $dir        Dir of items
# 
# Return:
#   Return list of couters, first item is count of parsed files,
#   second is count of parsed methods.
#
sub Parse($$$)
{
	my ($tag, $subject, $dir) = @_;
	my @items; #<$dir/*>;
	my $fh = new IO::File;
	my $source;
	my $file_name;
	my $parsed_count = 0;
	my $parsed_acl_call_count = 0;

	# get all php in dir and its subdirs
	find(sub { push @items, $File::Find::name if /\.php$/ }, $dir);
	@items = sort(@items);

	# For all items
	foreach my $item (@items)
	{
		# Get file name and filter non PHP files
		if ($item =~ /\/(\w+)\.php$/)
		{
			$file_name = $1;
		}
		else
		{
		    Warning("Skipping " . $item . " file");
		    next;
		}

		# Open source file
		if (! $fh->open("< $item"))
		{
			Warning("Cannot read from " . $subject . ": " . $item);
			next;
		}

		# Print info
		print STDERR "Parsing: " . $item . "\n";

		# Join file to one string
		$source = join("", <$fh>);
		
		# Increase counter
		$parsed_count = $parsed_count + 1;

		# For each ACL method call
		while ($source =~ m/acl_check_(edit|new|delete|confirm|view)\s*\(\s*((["']([^"']+)["'])|(get_class\s*\(\s*[\$]{1}this\s*\)\s*))\s*,\s*["']([^"']+)["']\s*(,)?/g)
		{
			# get values from the matched regex
			my ($action, $axo_section, $axo_value, $is_own) = ($1, $2, $6, defined($7));
			# section this 
			if ($axo_section =~ m/get_class\s*\(\s*[\$]{1}this\s*/g) {
				if (!("controller" eq $tag)) {
					print STDERR "get_class in view: " . $file_name . " : " . $tag . "\n";
					next; # skip
				}
				my $old = $axo_section;
				$axo_section = ucfirst($file_name) . "_Controller";
				print STDERR $old . " >> " . $axo_section . "\n";
			} else {
				$axo_section = substr($axo_section, 1, -1);
			}
			# increase counter
			$parsed_acl_call_count = $parsed_acl_call_count + 1;
			# unique treatment
			if ($unique)
			{
				my $index = $action . "#" . $axo_section . "#" . $axo_value . "#" . $is_own;

				if (defined($axos{$index}))
				{
					next; # skip already printed
				}

				$axos{$index} = 1;
			}
			# output values
			print "\"" . $action . "\";\"" . $axo_section . "\";\""
				. $axo_value . "\";\"" . ($is_own ? 1 : 0) . "\";\n";
		}

		# Close source file
		$fh->close;
	}
	
	return ($parsed_count, $parsed_acl_call_count);
}

