#!/usr/bin/perl

use strict;
use DBI;
use Getopt::Std;
use Data::Dumper;
use warnings;

if ($#ARGV != 3) {
  print "Wrong number of parameters", "\n";
  exit 0;
}

$ENV{PERL_LWP_SSL_VERIFY_HOSTNAME}=0;

use SOAP::Lite;
my $lbilling = SOAP::Lite
   -> uri("http://sip.nfx.czf/lBilling")
   -> proxy('https://'.$ARGV[0].':'.$ARGV[1].'@sip.nfx.czf/cgi-bin/admin/lbilling/soap.pl');

unless ( $lbilling ) {
  print "Could not create SOAP instance", "\n";
  exit 0;
}

my $result;
my $i;

my $account = {"billingid" => $ARGV[2], "limit" => $ARGV[3]};

$result = $lbilling->set_account_limit($account)->result;

if ( exists($result->{"status"}) and $result->{"status"} ) {
   $result = $result->{"data"};

   #print $result->{"billingid"}, ";";
   #print $result->{"from"}, ";";
   #print $result->{"to"}, "\n";

   Dumper($result);

   exit 1;
} else {
   if ( exists($result->{"error"}) ) {
        my $error = $result->{"error"};
        my $errcount = @{$result->{"error"}};
        for ($i=0; $i<$errcount; $i++)
        {
            print $result->{"error"}->[$i], "\n";
        } 
   }
   exit 0;
}
