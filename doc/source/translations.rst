Translating AgenDAV
===================

AgenDAV translation system is based on labels which get translated into
full sentences/paragraphs. They can contain placeholders which get replaced
by the system depending on the context.

How to add a translation
------------------------

Consider the ``en_US`` language to be the master reference language file.

1. Copy the directory ``web/lang/en_US`` to a new directory inside
   ``web/lang`` with the name of the locale of the new language. For example::
  
   $ cp -R web/lang/en_US web/lang/fr_FR

2. Rename ``en_US.php`` inside ``fr_FR/`` directory to ``fr_FR.php``

3. Edit all strings on the file. Make sure you save it using UTF-8 encoding.

4. Search the corresponding `CodeIgniter translation
   <http://mygengo.com/string/p/codeigniter-2-1>`_ and download its zip file.

5. Uncompress the CodeIgniter translation into ``web/application/language``

6. Rename the uncompressed directory to have the same name as the locale.  For example::

   $ cd web/application/language
   $ mv french fr_FR

7. You're done! Set :confval:`default_language` to your new language name
(``fr_FR`` in our example)
