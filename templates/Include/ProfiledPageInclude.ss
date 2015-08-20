<% if $Message %>
<p>$Message</p>
<% end_if %>

<% if $ShowThanks %>
<div class="profiled-page profiled-thanks">
    <span class="typography">
        $ProfiledThanksContent
    </span>
</div>
<% else_if $CurrentMember %>
<div class="profiled-page profiled-update">
    <span class="typography">
        $ProfiledUpdateContent
    </span>

    $CurrentMember.ProfiledMemberForm('update')

</div>
<% else %>
<div class="profiled-page profiled-register">
    <span class="typography">
        $ProfiledRegisterContent
    </span>

    $ProfiledMemberForm('register')
</div>
<% end_if %>
