Text extraction for Apache Solr + TYPO3
=======================================

This TYPO3 extension provides a hook/aspect that uses the signal of ext:solrfal during indexing to extract the contents 
of known text files.
 
It uses the binary `pdftotext` for this (when present on the machine) and has a fallback to the standalone apache Tika jar (when present on the system).

There are some additional checks when processing pdf files to determine if the contents is encrypted. 
If encrypted it tries the fallback to `tika`. 

