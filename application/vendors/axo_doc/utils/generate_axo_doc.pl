#!/usr/bin/perl
#
# Script for generating documentation of all used AXO in the FreenetIS.
# The documentation is generated in a form of an XML file.
#
# Info log is written to stderr.
#
# @author Ondřej Fibich
# @version 1.0
#

use strict;
use warnings;
use XML::Writer;
use XML::DOM;
use IO::File;
use Data::Dumper;
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
# XML Writer
my $xml_writer;


my $XML_FILE		= "../axo_doc.xml";
# comments on?
my $comments		= 1;
# cache
my %xml_old = ();

# UTF-8 set
use open ':encoding(utf8)';
binmode STDOUT, ":utf8";

# render style
XML::DOM::setTagCompression (\&my_tag_compression);
sub my_tag_compression
{
	return 1;
}

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
if ($#ARGV > 0)
{
	if ($#ARGV == 1 && ($ARGV[0] eq "-nc"))
	{
		$comments = 0;
	}
	else
	{
		print STDERR "usage: perl generate_axo_doc.pl [-nc]\n";
		exit(1);
	}
}

# Read olg config
# Is readable?
if (!(-r $XML_FILE))
{
	Error("Cannot read from file: " . $XML_FILE, 2);
}

# Read
my $parser = new XML::DOM::Parser;
my $xml_reader = $parser->parsefile($XML_FILE);

# Load all elements from old config
my $readed_items = $xml_reader->getElementsByTagName("object");
# All items
for (my $i = 0; $i < $readed_items->getLength; $i++)
{
	my $readed_item = $readed_items->item($i);
	my $i_name = $readed_item->getAttributeNode("name")->getValue;
	my $i_type = $readed_item->getAttributeNode("type")->getValue;
	# not view
	if (!($i_type eq "view"))
	{
		$xml_old{$i_type . "#" . $i_name . "#"}{"name"} = $i_name;
		$xml_old{$i_type . "#" . $i_name . "#"}{"type"} = $i_type;

		if (defined($readed_item->getAttributeNode("comment-en")))
		{
			$xml_old{$i_type . "#" . $i_name . "#"}{"comment-en"} =
					$readed_item->getAttributeNode("comment-en")->getValue;
		}

		if (defined($readed_item->getAttributeNode("comment-cs")))
		{
			$xml_old{$i_type . "#" . $i_name . "#"}{"comment-cs"} =
					$readed_item->getAttributeNode("comment-cs")->getValue;
		}

		if (defined($readed_item->getAttributeNode("hide")))
		{
			$xml_old{$i_type . "#" . $i_name . "#"}{"hide"} =
					$readed_item->getAttributeNode("hide")->getValue;
		}

		my $readed_methods = $readed_item->getElementsByTagName("method");
		# All methods of current item
		for (my $u = 0; $u < $readed_methods->getLength; $u++)
		{
			my $readed_method = $readed_methods->item($u);
			my $m_name = $readed_method->getAttributeNode("name")->getValue;

			$xml_old{$i_type . "#" . $i_name . "#" . $m_name} = $readed_method->toString;
		}
	}
	else
	{
		$xml_old{$i_type . "#" . $i_name . "#####view"} = $readed_item->toString;
	}
}
$xml_reader->dispose;


# Create XML Writer
$xml_writer = new XML::Writer(DATA_MODE => 1, DATA_INDENT => 4, UNSAFE => 1);
my $output_file;
# Open ouput file for write
eval
{
	$output_file = new IO::File(">$XML_FILE");
	$output_file or die();
};
# Error in open?
Error("Cannot write to file: $XML_FILE", 3) if ($@);
# Set ouput file to writer
binmode($output_file, ':utf8');
$xml_writer->setOutput($output_file);
# Header
$xml_writer->xmlDecl('UTF-8');
# Set doctype
$xml_writer->raw("\n<!DOCTYPE axoDocumentation SYSTEM \"axo_doc.dtd\">\n");
# Root tag
$xml_writer->startTag("axoDocumentation");

# Parse controllers
print STDERR "PARSING CONTROLLERS\n";
print STDERR "=====================================================================\n";
my ($c_count, $m_count) = Parse("controller", "Controllers", $CONROLLER_DIR);
print STDERR $c_count . " controllers parsed, " . $m_count . " ACL call founded.\n\n";
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

# End root tag
$xml_writer->endTag();
# Generate end of document
$xml_writer->end();

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
	my @items; #= <$dir/*>;
	my $fh = new IO::File;
	my $source;
	my $file_name;
	my $parsed_count = 0;
	my $parsed_acl_call_count = 0;
	my $parsed_acl_call_item_count;
	my $parsed_acl_call_item_method_count;
	my $added_new_method_count;

	# get all php in dir and its subdirs
	find(sub { push @items, $File::Find::name if /\.php$/ }, $dir);
	@items = sort(@items);

	# For all items
	foreach my $item (@items)
	{
		# Get file name and filter non PHP files
		if ($item =~ /(.+)\.php$/)
		{
			$file_name = substr($1, length($dir) + 1);
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

		# Create object tag
		if (defined($xml_old{$tag . "#" . $file_name . "#####view"}))
		{
			$xml_writer->raw("\n    " . $xml_old{$tag . "#" . $file_name . "#####view"});
			next; # done
		}
		else
		{
			my %attrs = ();

			if (defined($xml_old{$tag . "#" . $file_name . "#"}))
			{
				if (defined($xml_old{$tag . "#" . $file_name . "#"}{"comment-en"}))
				{
					$attrs{"comment-en"} = $xml_old{$tag . "#" . $file_name . "#"}{"comment-en"};
				}
				if (defined($xml_old{$tag . "#" . $file_name . "#"}{"comment-cs"}))
				{
					$attrs{"comment-cs"} = $xml_old{$tag . "#" . $file_name . "#"}{"comment-cs"};
				}
				if (defined($xml_old{$tag . "#" . $file_name . "#"}{"hide"}))
				{
					$attrs{"hide"} = $xml_old{$tag . "#" . $file_name . "#"}{"hide"};
				}
			}

			$xml_writer->startTag("object", "name" => $file_name, "type" => $tag, %attrs);
		}

		# Methods
		my @methods = split(m/function\s+[a-zA-Z0-9_]+\s*\(/g, $source);

		# Methods name
		my @methods_names = ();
		my @methods_go_throught = ();
		my $methods_names_index = 0;

		if (!("view" eq $tag))
		{
			shift(@methods); # remove first elem (start of class)

			while ($source =~ m/((?:[a-z0-9_*]+\s+)*)function\s+([a-zA-Z0-9_]+)\s*\(/g)
			{
				my ($method_details, $method_name, $methos_attrs) = ($1, $2, $3);

				$methods_names[$methods_names_index] = $method_name;
				$methods_go_throught[$methods_names_index] = (
					!($method_details =~ m/static/g) &&
					!($method_details =~ m/private/g) &&
					!($method_details =~ m/protected/g) &&
					!($method_name =~ /^valid_/)
				);
				$methods_names_index = $methods_names_index + 1;
			}
		}

		# Reset index
		$methods_names_index = 0;
		
		# For each method
		# regex:
		#       group(1):   Function specification: private, static, etc.
		#       group(2):   Function name
		#       group(3):   Args of function
		foreach my $method_body (@methods)
		{
			# Reset counter
			$parsed_acl_call_item_method_count = 0;
			$added_new_method_count = 0;

			# If enabled for checking of AXO
			if (("view" eq $tag) || $methods_go_throught[$methods_names_index])
			{
				# For each ACL method call
				while ($method_body =~ m/acl_check_(edit|new|delete|confirm|view)\s*\(\s*((["']([^"']+)["'])|(get_class\s*\(\s*[\$]{1}this\s*\)\s*))\s*,\s*["']([^"']+)["']\s*(,)?/g)
				{
					# get values from the matched regex
					my ($action, $axo_section, $axo_value, $is_own) = ($1, $2, $6, defined($7) ? "true" : "false");
					# section this 
					if ($axo_section =~ m/get_class\s*\(\s*[\$]{1}this\s*/g)
					{
						if (!("controller" eq $tag))
						{
							print STDERR "get_class in view: " . $file_name . " : " . $tag . "\n";
							next; # skip
						}
						my $old = $axo_section;
						$axo_section = ucfirst($file_name) . "_Controller";
						print STDERR $old . " >> " . $axo_section . "\n";
					}
					else
					{
						$axo_section = substr($axo_section, 1, -1);
					}
					# create tag method?
					if ($parsed_acl_call_item_method_count == 0)
					{
						if (!("view" eq $tag))
						{
							if (defined($xml_old{$tag . "#" . $file_name . "#" . $methods_names[$methods_names_index]}))
							{
								$xml_writer->raw("\n        " . $xml_old{$tag . "#" . $file_name . "#" . $methods_names[$methods_names_index]});
							}
							else
							{
								$added_new_method_count = $added_new_method_count + 1;
								$xml_writer->startTag("method", "name" => $methods_names[$methods_names_index]);
							}
						}

						# comment with source code
						if ($comments && !(defined($xml_old{$tag . "#" . $file_name . "#" . $methods_names[$methods_names_index]})))
						{
							my $method_body_comments = $method_body;
							$method_body_comments =~ s/[-][-]//mg; # remove --
							$xml_writer->comment((defined($methods_names[$methods_names_index]) ? $methods_names[$methods_names_index] . "(" : "") . $method_body_comments);
						}
					}
					# increase counter
					$parsed_acl_call_count = $parsed_acl_call_count + 1;
					$parsed_acl_call_item_method_count = $parsed_acl_call_item_method_count + 1;

					if (!(defined($xml_old{$tag . "#" . $file_name . "#" . $methods_names[$methods_names_index]})))
					{
						# Create AXo tag
						$xml_writer->startTag(
							"axo",
							"usage_type"	=> "unknown",
							"section"		=> $axo_section,
							"value"			=> $axo_value,
							"action"		=> $action,
							"own"			=> $is_own
						);

						# End AXO tag
						$xml_writer->endTag();
					}
				}
				
				# end method tag?
				if ($parsed_acl_call_item_method_count > 0)
				{
					if (!("view" eq $tag) && !(defined($xml_old{$tag . "#" . $file_name . "#" . $methods_names[$methods_names_index]})))
					{
						$xml_writer->endTag();
					}
				}
				elsif ($tag eq "controller")
				{
					$added_new_method_count = $added_new_method_count + 1;
					$xml_writer->startTag("method", "name" => $methods_names[$methods_names_index]);
					$xml_writer->endTag();
				}
			}

			# next index
			$methods_names_index = $methods_names_index + 1;
		}

		# Hack for raw indent
		if ($added_new_method_count == 0 && $parsed_acl_call_item_method_count > 0) {
			#$xml_writer->raw("\n    ");
		}

		# End tag object
		$xml_writer->endTag();

		# Close source file
		$fh->close;
	}
	
	return ($parsed_count, $parsed_acl_call_count);
}

