<nav class="profiled-nav clearfix">

    <h5 class="$CSSClass">$CurrentMember.FirstName $CurrentMember.Surname</h5>

    <% if $fullLinks %>
        $TabStrip(true)
    <% else %>
        $TabStrip()
    <% end_if %>
</nav>
<% require css('profiled/css/profiled.css') %>

