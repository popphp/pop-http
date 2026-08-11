<?php

// Ground truth extracted from the pre-Task-2 hand-typed $optionValues table
// (git show 5b406e0:src/Client/Handler/Curl/Options.php), for the curl-constant
// integer values that were shared by more than one CURLOPT_*/CURLSSLOPT_* name.
// array_search() on that table always resolved to whichever name was declared
// first (the table was declared alphabetically), so this fixture captures that
// winner per colliding value - the behavior getOptionNameByValue() must preserve
// now that the table itself has been replaced with constant()/defined() lookups.
return array (
  32 => 'CURLOPT_SSLVERSION',
  48 => 'CURLOPT_DIRLISTONLY',
  50 => 'CURLOPT_APPEND',
  119 => 'CURLOPT_USE_SSL',
  10009 => 'CURLOPT_INFILE',
  10026 => 'CURLOPT_SSLKEYPASSWD',
  10063 => 'CURLOPT_KRBLEVEL',
  10102 => 'CURLOPT_ACCEPT_ENCODING',
);
