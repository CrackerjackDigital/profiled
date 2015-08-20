<$Tag class="CompositeField $extraClass <% if ColumnCount %>multicolumn<% end_if %>">
<% if $Tag == 'fieldset' && $Legend %>
<legend>$Legend</legend>
<% end_if %>
asdasdasdad
<% loop $FieldList %>
<% if $ColumnCount %>
<div class="column-{$ColumnCount} $FirstLast">
    $Field
</div>
<% else %>
sadasdasdad
$Field
<% end_if %>
<% end_loop %>
</$Tag>
