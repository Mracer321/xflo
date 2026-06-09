{{--
    Shared lead form. Expects:
      $action      — form action URL
      $method      — 'POST' (create) or 'PUT' (update)
      $lead        — existing Lead model, or null when creating
      $statuses    — status key => label map
      $submitLabel — submit button text
--}}
@php($lead = $lead ?? null)

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    {{-- Validation summary --}}
    @if ($errors->any())
        <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
            Please correct the highlighted fields below.
        </div>
    @endif

    {{-- Core details --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6">
        <h3 class="mb-4 text-sm font-semibold text-gray-900">Business details</h3>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

            {{-- Business Name --}}
            <div class="sm:col-span-2">
                <label for="business_name" class="block text-sm font-medium text-gray-700 mb-1">
                    Business Name <span class="text-red-500">*</span>
                </label>
                <input id="business_name" name="business_name" type="text"
                    value="{{ old('business_name', $lead->business_name ?? '') }}" required
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('business_name') border-red-500 @enderror">
                @error('business_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Owner Name --}}
            <div>
                <label for="owner_name" class="block text-sm font-medium text-gray-700 mb-1">Owner Name</label>
                <input id="owner_name" name="owner_name" type="text"
                    value="{{ old('owner_name', $lead->owner_name ?? '') }}"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('owner_name') border-red-500 @enderror">
                @error('owner_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Category --}}
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <input id="category" name="category" type="text"
                    value="{{ old('category', $lead->category ?? '') }}"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('category') border-red-500 @enderror">
                @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Mobile Number --}}
            <div>
                <label for="mobile_number" class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                <input id="mobile_number" name="mobile_number" type="text"
                    value="{{ old('mobile_number', $lead->mobile_number ?? '') }}"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('mobile_number') border-red-500 @enderror">
                @error('mobile_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- WhatsApp Number --}}
            <div>
                <label for="whatsapp_number" class="block text-sm font-medium text-gray-700 mb-1">WhatsApp Number</label>
                <input id="whatsapp_number" name="whatsapp_number" type="text"
                    value="{{ old('whatsapp_number', $lead->whatsapp_number ?? '') }}"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('whatsapp_number') border-red-500 @enderror">
                @error('whatsapp_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input id="email" name="email" type="email"
                    value="{{ old('email', $lead->email ?? '') }}"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('email') border-red-500 @enderror">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Status --}}
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('status') border-red-500 @enderror">
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(old('status', $lead->status ?? 'new') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Address --}}
            <div class="sm:col-span-2">
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea id="address" name="address" rows="2"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('address') border-red-500 @enderror">{{ old('address', $lead->address ?? '') }}</textarea>
                @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- Web presence --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6">
        <h3 class="mb-4 text-sm font-semibold text-gray-900">Web presence</h3>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

            {{-- Google Business URL --}}
            <div class="sm:col-span-2">
                <label for="google_business_url" class="block text-sm font-medium text-gray-700 mb-1">Google Business URL</label>
                <input id="google_business_url" name="google_business_url" type="url" placeholder="https://…"
                    value="{{ old('google_business_url', $lead->google_business_url ?? '') }}"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('google_business_url') border-red-500 @enderror">
                @error('google_business_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Facebook URL --}}
            <div>
                <label for="facebook_url" class="block text-sm font-medium text-gray-700 mb-1">Facebook URL</label>
                <input id="facebook_url" name="facebook_url" type="url" placeholder="https://…"
                    value="{{ old('facebook_url', $lead->facebook_url ?? '') }}"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('facebook_url') border-red-500 @enderror">
                @error('facebook_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Instagram URL --}}
            <div>
                <label for="instagram_url" class="block text-sm font-medium text-gray-700 mb-1">Instagram URL</label>
                <input id="instagram_url" name="instagram_url" type="url" placeholder="https://…"
                    value="{{ old('instagram_url', $lead->instagram_url ?? '') }}"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('instagram_url') border-red-500 @enderror">
                @error('instagram_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Website Exists --}}
            <div class="sm:col-span-2">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="website_exists" value="1"
                        @checked(old('website_exists', $lead->website_exists ?? false))
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-medium text-gray-700">Website exists</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Notes --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6">
        <label for="notes" class="block text-sm font-semibold text-gray-900 mb-2">Notes</label>
        <textarea id="notes" name="notes" rows="4"
            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('notes') border-red-500 @enderror">{{ old('notes', $lead->notes ?? '') }}</textarea>
        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('leads.index') }}"
            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Cancel
        </a>
        <button type="submit"
            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            {{ $submitLabel }}
        </button>
    </div>
</form>
