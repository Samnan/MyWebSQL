CREATE [ OR REPLACE ] FUNCTION
    name ( [ [ argmode ] [ argname ] argtype [ { DEFAULT | = } default_expr ] [, ...] ] )
    [ RETURNS rettype | RETURNS TABLE ( column_name column_type [, ...] ) ]
{ LANGUAGE lang_name
	| WINDOW
	| IMMUTABLE | STABLE | VOLATILE
	| CALLED ON NULL INPUT | RETURNS NULL ON NULL INPUT | STRICT
	| [ EXTERNAL ] SECURITY INVOKER | [ EXTERNAL ] SECURITY DEFINER
	| COST execution_cost
	| ROWS result_rows
	| SET configuration_parameter { TO value | = value | FROM CURRENT }
	| AS 'definition'
	| AS 'obj_file', 'link_symbol'
} ...
[ WITH ( attribute [, ...] ) ]