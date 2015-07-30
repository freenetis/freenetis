#!/usr/bin/perl

use strict;
use DBI;
use Getopt::Std;
use Data::Dumper;
use warnings;

if ($#ARGV != 4) {
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

my $account = {"billingid" => $ARGV[2], "from" => $ARGV[3], "to" => $ARGV[4]};

$result = $lbilling->get_account_calls($account)->result;

if ( exists($result->{"status"}) and $result->{"status"} ) {
   $result = $result->{"data"};

   print $result->{"billingid"}, ";";
   print $result->{"from"}, ";";
   print $result->{"to"}, "\n";

   unless ( $result->{"calls"} ) {
      print "0", "\n";
      exit 1;
   }

   my $count = @{$result->{"calls"}};

   print $count, "\n";

   for ($i=0; $i<$count; $i++)
   {
      print $result->{"calls"}[$i]->{"provider"}, ";";
      print $result->{"calls"}[$i]->{"rate_vat"}, ";";
      print $result->{"calls"}[$i]->{"subscriber"}, ";";
      print $result->{"calls"}[$i]->{"account"}, ";";
      print $result->{"calls"}[$i]->{"area"}, ";";
      print $result->{"calls"}[$i]->{"callee"}, ";";
      print $result->{"calls"}[$i]->{"status"}, ";";
      print $result->{"calls"}[$i]->{"emergency"}, ";";
      print $result->{"calls"}[$i]->{"rate_sum"}, ";";
      print $result->{"calls"}[$i]->{"currency"}, ";";
      print $result->{"calls"}[$i]->{"end_date"}, ";";
      print $result->{"calls"}[$i]->{"callcon"}, ";";
      print $result->{"calls"}[$i]->{"caller"}, ";";
      print $result->{"calls"}[$i]->{"type"}, ";";
      print $result->{"calls"}[$i]->{"start_date"}, ";";
      print $result->{"calls"}[$i]->{"result"}, "\n";
   }
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
