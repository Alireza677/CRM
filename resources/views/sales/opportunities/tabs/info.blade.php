<!-- Opportunity Details -->
<div class="bg-white rounded-lg shadow-md p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Main Information -->
                    <div>
                        <h2 class="text-lg font-semibold mb-4">اطلاعات اصلی</h2>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">نام فرصت فروش</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $opportunity->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">سازمان</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $opportunity->organization->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">مخاطب</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $opportunity->contact->name ?? '-' }}</dd>
                            </div>
                            <!-- مرحله فروش -->
                            <div class="mb-2">
                                <div class="text-sm text-gray-600">مرحله فروش</div>
                                <div class="text-sm text-gray-900 font-semibold">
                                     {{ $opportunity->stage ?? '-' }}
                                </div>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">نوع</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $opportunity->type ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">منبع</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $opportunity->source ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Additional Information -->
                    <div>
                        <h2 class="text-lg font-semibold mb-4">اطلاعات تکمیلی</h2>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">ارجاع به</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $opportunity->assignedTo->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">درصد موفقیت</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $opportunity->success_rate ?? '-' }}%</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">مبلغ</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ number_format($opportunity->amount) ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">تاریخ پیگیری بعدی</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    @if($opportunity->next_follow_up)
                                        {{ \Carbon\Carbon::parse($opportunity->next_follow_up)->format('Y/m/d') }}
                                    @else
                                        -
                                    @endif
                                </dd>
                            </div>
                            @php
                                $lastNote = $opportunity->lastNote ?? null;
                                $displayBody = $lastNote?->body ?? '—';

                                if ($lastNote) {
                                    // استخراج @username ها
                                    preg_match_all('/@([^\s@]+)/u', $lastNote->body, $matches);
                                    $mentionedUsernames = array_unique($matches[1] ?? []);

                                    // گرفتن کاربران با username و جایگزینی با نام
                                    if (!empty($mentionedUsernames)) {
                                        $mentionedUsers = \App\Models\User::whereIn('username', $mentionedUsernames)->get()->keyBy('username');
                                        foreach ($mentionedUsers as $username => $user) {
                                            $displayBody = str_replace("@{$username}", '@' . $user->name, $displayBody);
                                        }
                                    }
                                }
                            @endphp

                            <div>
                                <strong>آخرین یادداشت:</strong>
                                {!! nl2br(e($displayBody)) !!}
                            </div>

                            <div>
                                <strong>تاریخ یادداشت:</strong>
                                {{ $lastNote?->created_at
                                    ? \App\Helpers\DateHelper::toJalali($lastNote->created_at)
                                    : '—' }}
                            </div>

                            <div>
                                <strong>ثبت‌کننده یادداشت:</strong>
                                {{ $lastNote?->user?->name ?? '—' }}
                            </div>

                        </dl>
                    </div>
                </div>

                <!-- Description -->
                <div class="mt-6">
                    <h2 class="text-lg font-semibold mb-4">توضیحات</h2>
                    <p class="text-sm text-gray-900">{{ $opportunity->description ?? '-' }}</p>
                </div>
            </div>