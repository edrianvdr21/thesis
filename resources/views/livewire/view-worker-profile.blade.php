<div class="max-w-4xl mx-auto mt-8 px-4">
    <h1 class="text-3xl font-bold mb-4">{{ $userProfile->first_name }} {{ $userProfile->last_name }}'s Profile</h1>

    <div class="bg-white shadow-md rounded-md p-6 mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="col-span-1">
            <img
                class="w-full h-80 object-cover object-center rounded-md"
                src="{{ asset('storage/' . $userProfile->profile_picture) }}"
                alt="{{ $userProfile->first_name }} {{ $userProfile->last_name }}'s Profile Picture"
            >
        </div>
        <div class="col-span-1">
            <div>
                <p class="text-gray-700 text-lg mb-2">
                    <span class="font-semibold">Category:</span> {{ $workerProfile->category->category }}
                </p>
                <p class="text-gray-700 text-lg mb-2">
                    <span class="font-semibold">Service:</span> {{ $workerProfile->service->service }}
                </p>
                <p class="text-gray-700 text-lg mb-2">
                    <span class="font-semibold">Working Day/s:</span>
                    @if(!empty($availableDays))
                        {{ implode(', ', $availableDays) }}
                    @else
                        Not available on any day.
                    @endif
                </p>
                <p class="text-gray-700 text-lg mb-2">
                    <span class="font-semibold">Time Availability:</span>
                    {{ DateTime::createFromFormat('H:i:s', $workerProfile->start_time)->format('g:i A') }}
                    to
                    {{ DateTime::createFromFormat('H:i:s', $workerProfile->end_time)->format('g:i A') }}
                </p>
            </div>

            <div class="mt-4">
                <h2 class="text-xl font-bold mb-2">Specific Services</h2>
                <ul>
                    @foreach($specificServices as $specificService)
                        <label
                            role="radio"
                            tabindex="0"
                            class="inline-flex items-center cursor-pointer rounded-md border-2 p-3 mb-2 focus:outline-none
                                @if ($selected_specific_service_id == $specificService->id)
                                    bg-blue-100 border-blue-800
                                @else
                                    bg-white border-gray-300 hover:border-gray-500 focus:border-gray-500
                                @endif"
                            wire:click="selectSpecificService({{ $specificService->id }})"
                        >
                            <input
                                type="radio"
                                name="specific_service"
                                class="hidden"
                                value="{{ $specificService->id }}"
                                @if ($selected_specific_service_id == $specificService->id) checked @endif
                            >
                            <span class="ml-2">{{ $specificService->specific_service }}</span>
                        </label>
                    @endforeach


                </ul>
            </div>

            <div class="mt-4">
                <div wire:loading.remove wire:target="selectSpecificService">
                    @if ($selectedSpecificService)
                        <p class="text-gray-700 mb-2"><span class="font-semibold">Description:</span> {{ $selectedSpecificService->description }}</p>
                        <p class="text-gray-700 mb-2">{{ $selectedSpecificService->duration }} minutes</p>
                        <p class="text-2xl font-bold text-blue-900">₱{{ $selectedSpecificService->price }}</p>
                    @endif
                </div>
                <div wire:loading wire:target="selectSpecificService" class="text-gray-700 animate-pulse">
                    Loading...
                </div>
            </div>
        </div>
    </div>

    @if(Auth::user()->userProfile->is_verified == 1)
        <div class="bg-white shadow-md rounded-md p-6 mt-8">
            <h2 class="text-2xl font-bold mb-4">Book a Service</h2>

            @if ($errors->any())
                <div class="text-red-600 text-base" role="alert" aria-live="polite">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">{{ session('success') }}</strong>
                </div>
            @endif

            <form action="/book" method="POST">
                @csrf
                {{-- Hidden fields to pass on controller --}}
                <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                <input type="hidden" name="worker_id" value="{{ $workerProfile->id }}">
                <input type="hidden" name="specific_service_id" value="{{ $selected_specific_service_id }}">

                <div class="mb-4">
                    <label for="booking_date" class="block text-sm font-medium text-gray-700">Booking Date</label>
                    <input type="date" name="booking_date" id="booking_date" aria-label="Booking Date" aria-required="true" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div class="mb-4">
                    <label for="booking_time" class="block text-sm font-medium text-gray-700">Booking Time</label>
                    <input type="time" name="booking_time" id="booking_time" aria-label="Booking Time" aria-required="true" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div class="mb-4">
                    <label for="booking_notes" class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea rows="5" name="booking_notes" id="booking_notes" aria-label="Notes" aria-required="true" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Book a Service
                </button>
            </form>
        </div>
    @elseif(Auth::user()->userProfile->is_verified != 1)
        <p class="bg-red-200 text-center mt-4 py-4">
            Verify your account to book a service.
        </p>
    @endif

    <section class="bg-gray-100 py-12">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold mb-8">Customer Reviews</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($reviews as $review)
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <img
                                        class="h-10 w-10 rounded-full mr-4"
                                        src="{{ asset('storage/' . $review->user->userProfile->profile_picture) }}"
                                        alt="{{ $review->user->userProfile->first_name }} {{ $review->user->userProfile->last_name }}'s profile_picture"
                                    >
                                    <div>
                                        <p class="text-gray-900 font-bold">
                                            {{ $review->user->userProfile->first_name }} {{ $review->user->userProfile->last_name }}
                                        </p>
                                        <p class="text-gray-600 text-sm">
                                            {{ $review->specificService->specific_service }}
                                        </p>
                                        <div class="flex items-center">
                                            @for ($i = 1; $i <= 5; $i++)
                                                @if ($i <= $review->rating)
                                                    <svg class="h-5 w-5 fill-current text-yellow-500" viewBox="0 0 24 24">
                                                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2M12 15.33L14.67 17.41L13.79 14.39L16.39 12.73L13.35 12.33L12 9.5L10.65 12.33L7.61 12.73L10.21 14.39L9.33 17.41L12 15.33Z" />
                                                    </svg>
                                                @else
                                                    <svg class="h-5 w-5 fill-current text-gray-400" viewBox="0 0 24 24">
                                                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2M12 15.33L14.67 17.41L13.79 14.39L16.39 12.73L13.35 12.33L12 9.5L10.65 12.33L7.61 12.73L10.21 14.39L9.33 17.41L12 15.33Z" />
                                                    </svg>
                                                @endif
                                            @endfor
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <p class="text-gray-700 text-base mt-4">
                                {{ $review->review }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
