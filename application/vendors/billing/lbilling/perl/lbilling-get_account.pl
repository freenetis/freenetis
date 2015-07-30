#!/usr/bin/perl

use strict;
use DBI;
use Getopt::Std;
use Data::Dumper;
use warnings;

if ($#ARGV != 2) {
  print "Wrong number of parameters", "\n";
  exit 0;
}

my $account = {"billingid" => $ARGV[2]};

use SOAP::Lite +autodispatch =>
   uri => "http://sip.nfx.czf/lBilling",
   proxy => 'https://'.$ARGV[0].':'.$ARGV[1].'@sip.nfx.czf/cgi-bin/admin/lbilling/soap.pl';

my $lbilling = lBilling->new();

unless ( $lbilling ) {
  print "Could not create SOAP instance", "\n";
  exit 0;
}

my $result;
my $i;

if ( $result = $lbilling->get_account($account) ) {
   print $result->{"valid_to"}, ";";
   print $result->{"valid_from"}, ";";
   print $result->{"descr"}, ";";
   print $result->{"state"}, ";";
   print $result->{"billingid"}, ";";
   print $result->{"currency"}, ";";
   print $result->{"ballance"}, ";";
   print $result->{"limit"}, ";";
   print $result->{"type"}, ";";
   print $result->{"partner"}, "\n";

   unless ( $result->{"subscribers"} ) {
	  print "0", "\n";
      exit 1;
   }

   my $count = @{$result->{"subscribers"}};

   print $count, "\n";

   for ($i=0; $i<$count; $i++)
   {
      print $result->{"subscribers"}[$i]->{"valid_to"}, ";";
      print $result->{"subscribers"}[$i]->{"valid_from"}, ";";
      print $result->{"subscribers"}[$i]->{"descr"}, ";";
      print $result->{"subscribers"}[$i]->{"state"}, ";";
      print $result->{"subscribers"}[$i]->{"billingid"}, ";";
      print $result->{"subscribers"}[$i]->{"tarif"}, ";";
      print $result->{"subscribers"}[$i]->{"cid"}, ";";
      print $result->{"subscribers"}[$i]->{"limit"}, "\n";
   }
   exit 1;
} else {
   my $error = $lbilling->get_error();
   my $errcount = @{$lbilling->get_error()};
   for ($i=0; $i<$errcount; $i++)
   {
      print $lbilling->get_error()->[$i], "\n";
   }
   exit 0;
}
