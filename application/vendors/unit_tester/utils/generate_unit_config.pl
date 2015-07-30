#!/usr/bin/perl
#
# Script for generating XML document for unit test config
#
# Required libraries: libxml-writer-perl, libxml-dom-perl
#
# @author Ondřej Fibich
# @version 1.1
#

use strict;
use warnings;
use XML::Writer;
use XML::DOM;
use IO::File;

# Base app dir
my $APP_DIR 		= "../..";
# Model dir
my $MODEL_DIR 		= $APP_DIR . "/models";
# Controller dir
my $CONROLLER_DIR	= $APP_DIR . "/controllers";
# Helper dir
my $HELPER_DIR		= $APP_DIR . "/helpers";
# XML config for unit testing
my $XML_CONFIG		= "unit_testing_config.xml";
# XML Writer
my $xml_writer;
# Old XML config
my %xml_old = ();
# Output file
my $output_file;

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
#   Return list of couters, first item is count of parsed files,
#   second is count of parsed methods.
#
sub Parse($$$);

# Already created config?
if (-e $XML_CONFIG)
{
	# Is readable?
	if (!(-r $XML_CONFIG))
	{
		Error("Cannot read from file: " . $XML_CONFIG, 2);
	}
	
	# Read
	my $parser = new XML::DOM::Parser;
	my $xml_reader = $parser->parsefile($XML_CONFIG);
	
	# Load all elements from old config
	for my $tag ("model", "helper", "controller")
	{
	    my $readed_items = $xml_reader->getElementsByTagName($tag);
	    # All items
	    for (my $i = 0; $i < $readed_items->getLength; $i++)
        {
            my $readed_item = $readed_items->item($i);
            my $readed_methods = $readed_item->getElementsByTagName("method");
            my $i_name = $readed_item->getAttributeNode("name")->getValue;
            # All methods of current item
            for (my $u = 0; $u < $readed_methods->getLength; $u++)
            {
                my $readed_method = $readed_methods->item($u);
                my $m_name = $readed_method->getAttributeNode("name")->getValue;
                my $autogen = $readed_method->getAttributeNode("autogenerate");
                # Autogeneration off?
                if ((not defined $autogen) or not ($autogen->getValue eq "on"))
                {
                    # Index of method
                    my $index = $tag . "#" . $i_name . "#" . $m_name;
                    # Add old method
                    $xml_old{$index} = $readed_method->toString;
                }
            }
        }
    }
    $xml_reader->dispose;
}

# Create XML Writer
$xml_writer = new XML::Writer(DATA_MODE => 1, DATA_INDENT => 4, UNSAFE => 1);
# Open ouput file for write
eval
{
	$output_file = new IO::File(">$XML_CONFIG");
	$output_file or die();
};
# Error in open?
Error("Cannot write to file: $XML_CONFIG", 3) if ($@);
# Set ouput file to writer
$xml_writer->setOutput($output_file);
# Header
$xml_writer->xmlDecl('UTF-8');
# Set doctype
$xml_writer->raw("\n<!DOCTYPE unit_test SYSTEM \"unit_testing_config.dtd\">\n");
# Root tag
$xml_writer->startTag("unit_test");

# Parse controllers
print "PARSING CONTROLLERS\n";
print "=====================================================================\n";
my ($c_count, $m_count) = Parse("controller", "Controllers", $CONROLLER_DIR);
print $c_count . " controllers parsed, " . $m_count . " methods founded.\n\n";
# Parse models
print "PARSING MODELS\n";
print "=====================================================================\n";
($c_count, $m_count) = Parse("model", "Model", $MODEL_DIR);
print $c_count . " models parsed, " . $m_count . " methods founded.\n\n";
# Parse helpers
print "PARSING HELPERS\n";
print "=====================================================================\n";
($c_count, $m_count) = Parse("helper", "Helper", $HELPER_DIR);
print $c_count . " helpers parsed, " . $m_count . " methods founded.\n\n";

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
	my @items = <$dir/*>;
	my $fh = new IO::File;
	my $source;
	my $file_name;
	my $parsed_count = 0;
	my $parsed_methods_count = 0;

	# For all items
	foreach my $item (@items)
	{
		# Get model name
		if ($item =~ /\/(\w+)\.php$/)
		{
			$file_name = $1;
		}
		else
		{
		    Warning("Skipping " . $item . " file");
		    next;
		}
		# Create tag for item
		$xml_writer->startTag($tag, "name" => $file_name);

		# Open source file
		if (! $fh->open("< $item"))
		{
			Warning("Cannot read from " . $subject . ": " . $item);
			next;
		}

		# Join file to one string
		$source = join("", <$fh>);
		
		# Increase counter
		$parsed_count = $parsed_count + 1;

		# For each method
		# regex:
		#       group(1):   Function specification: private, static, etc.
		#       group(2):   Function name
		#       group(3):   Args of function
		while ($source =~ m/((?:[a-z0-9_*]+\s+)*)function\s+(\w+)\s*\((.*?)\)\s*{/g)
		{
		    my ($method_details, $method_name, $methos_attrs) = ($1, $2, $3);

            # Throw up private and protected methods
            next if ($method_details =~ /(private)|(protected)/);
			# Throw up special methods
			next if ($method_name =~ /^__/);
			# Helpers allows just static methods
			next if ($dir eq $HELPER_DIR) and not ($method_details =~ /(static)/);
			# Controllers not allows static methods
			next if ($dir eq $CONROLLER_DIR) and ($method_details =~ /(static)/);
			# Controllers not allows valid_* methods
			next if ($dir eq $CONROLLER_DIR) and ($method_name =~ /(valid_)/);
			
			# Skip method if autogeneration is off (add old method to file)
			my $index = $tag . "#" . $file_name . "#" . $method_name;
			
			if (exists($xml_old{$index}))
			{
			    # User info
			    print "Skipping generation for " . $tag . ": " . $file_name;
			    print "#" . $method_name . "\n";
			    # add to document
			    $xml_writer->raw("\n        " . $xml_old{$index});
			    # Next method
			    next;
			}
            
			# Add method
			$xml_writer->startTag("method", "name" => $method_name, "autogenerate" => "on");
			
			# Increase counter
			$parsed_methods_count = $parsed_methods_count + 1;

			# Get args
			my @attributes = split(/,/, $methos_attrs);
			my $i = 0;

			# Add group of attributes
			$xml_writer->startTag("attributes");

			my @default_attrs;
			my $count_of_required = 0;

			# For each attribute
			foreach my $attribute (@attributes)
			{
				if ($attribute =~ /^\s*\$(\w+)\s*(?:=\s*["']?(.*?)["']?)?$/)
				{
					my $attr_name = $1;
					# Require param?
					$count_of_required++ if (!defined $2);
					# Value of attribute
					$default_attrs[$i] = (!defined $2 or $2 =~ /^(null|NULL)$/i) ? "" : "$2";
					# Add definition of attribute
					$xml_writer->emptyTag(
						"attribute", "name" => $attr_name,
						"default_value" => $default_attrs[$i++]
					);
				}
			}

			# End group of attributes
			$xml_writer->endTag();

			# Add data group
			$xml_writer->startTag("values");

			# Generate inpus from required and optional values
			# Increasing optional values by each input
			for (my $it = $count_of_required; $it <= scalar @attributes; $it++)
			{
				# Add data input group
				$xml_writer->startTag("input");

				my $default_attrs_count = 0;
				
				# Add default input values
				foreach my $val (@default_attrs)
				{
					# Increase filled
					$default_attrs_count++;
					# End loop
					last if ($default_attrs_count > $it);
					# Write param
					$xml_writer->emptyTag("param", "value" => $val);
				}

				# End data input group
				$xml_writer->endTag();
			}

			# End data group
			$xml_writer->endTag();

			# End method
			$xml_writer->endTag();
		}

		# Close source file
		$fh->close;

		# End tag of item
		$xml_writer->endTag();
	}
	
	return ($parsed_count, $parsed_methods_count);
}

