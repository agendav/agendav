<?php

$lang['db_invalid_connection_str'] = 'Não é possível determinar as configurações do banco de dados com base nas informações de conexão que você enviou.';
$lang['db_unable_to_connect'] = 'Não é possível conectar ao servidor de banco de dados usando as configurações fornecidas.';
$lang['db_unable_to_select'] = 'Não é possível selecionar o banco de dados especificado: %s';
$lang['db_unable_to_create'] = 'Não é possível criar o banco de dados especificado: %s';
$lang['db_invalid_query'] = 'A consulta que você submeteu não é válida.';
$lang['db_must_set_table'] = 'Você deve definir a tabela de banco de dados para ser usada com a sua consulta.';
$lang['db_must_use_set'] = 'Você deve usar o método  "SET" para atualizar uma entrada.';
$lang['db_must_use_index'] = 'Você deve especificar um índice para coincidir com as atualizações em lote.';
$lang['db_batch_missing_index'] = 'Uma ou mais linhas apresentadas do lote está faltando para atualizar o índice especificado.';
$lang['db_must_use_where'] = 'Atualizações não são permitidos, a menos que eles contêm um "WHERE" cláusula.';
$lang['db_del_must_use_where'] = 'Não é permitido excluir a menos que ele contenha "WHERE" ou "LIKE" na cláusa.';
$lang['db_field_param_missing'] = 'Para retornar os campos, é necessário o nome da tabela como um parâmetro.';
$lang['db_unsupported_function'] = 'Este recurso não está disponível para o banco de dados você está usando.';
$lang['db_transaction_failure'] = 'Falha na transação: um Rollback foi executado.';
$lang['db_unable_to_drop'] = 'Incapaz executar "DROP" no banco de dados especificado.';
$lang['db_unsuported_feature'] = 'Recurso não suportado na plataforma de banco de dados você está usando.';
$lang['db_unsuported_compression'] = 'O formato de compressão de arquivo que você escolheu não é suportado pelo seu servidor.';
$lang['db_filepath_error'] = 'Não é possível escrever no caminho do arquivo que você enviou.';
$lang['db_invalid_cache_path'] = 'O caminho do cache que você apresentou não é válido ou não possui permissão de escrita.';
$lang['db_table_name_required'] = 'Um nome de tabela é necessário para essa operação.';
$lang['db_column_name_required'] = 'O nome da coluna é necessário para esta operação.';
$lang['db_column_definition_required'] = 'É necessário definir uma coluna para esta operação.';
$lang['db_unable_to_set_charset'] = 'Não é possível conectar-se com o cliente com o charset definido: %s';
$lang['db_error_heading'] = 'Ocorreu um erro no banco de dados';
?>