@component('mail::message')

@isset($notifiable->name)
# {{ __('Hello :name,', ['name' => $notifiable->name]) }}
@else
# {{ __('Hello,') }}
@endisset

{{ __('Please confirm your email address by clicking the button below.') }}

@component('mail::button', ['url' => $url])
{{ __('Verify Email Address') }}
@endcomponent

{{ __('If you did not create an account, no further action is required.') }}

---

{{ __('Regards') }},

{{ config('app.name') }}

@endcomponent
