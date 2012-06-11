Translating AgenDAV
===================

AgenDAV translation system is based on labels which get translated into
full sentences/paragraphs. They can contain placeholders which get replaced
by the system depending on the context.

How to add a translation
------------------------
1. Access `AgenDAV project in Transifex
   <https://www.transifex.net/projects/p/agendav/>`_ and use the 
   *Create language_* to add the missing language. You'll have to wait 
   until I approve the request. Once you have it created, you'll be able 
   to use Transifex interface to translate AgenDAV strings.

2. Search the corresponding `CodeIgniter translation
   <http://mygengo.com/string/p/codeigniter-2-1>`_ and download its zip file.

3. Uncompress the CodeIgniter translation into ``web/application/language``

4. Edit ``web/config/languages.php`` and add a new entry like this::

    $config['lang_rels']['fr_FR'] = array(
            'codeigniter' => 'french',
    );

   This supposes the language file you downloadad for CodeIgniter resulted
   in a directory called ``french/``

5. You're done! Set :confval:`default_language` to your new language name
(``fr_FR`` in our example)
