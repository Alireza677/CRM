@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://app.akhgartabesh.com/images/logo.png" class="logo" alt="CRM AkhgarTabesh">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
