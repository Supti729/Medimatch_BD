<x-layouts.public title="About">
    <x-page-hero eyebrow="About" title="Medicine should not expire in a drawer while someone nearby needs it"
        description="Medimatch BD is a Software Engineering capstone project exploring how technology can connect unused, eligible medicines with people who need them in Bangladesh." />

    <div class="page-container py-12 sm:py-16">
        <div class="grid gap-10 lg:grid-cols-[1.3fr_1fr]">
            <x-prose>
                <h2 class="!mt-0">The problem</h2>
                <p>
                    Households often keep sealed medicines they no longer need after a treatment changes or ends. At the same time,
                    other people struggle to find a medicine nearby or to afford it. There is no simple, trusted way for the two to meet.
                </p>
                <h2>Our approach</h2>
                <p>
                    Medimatch BD is a location-based platform where one account can both request medicines and offer them. It is designed around four ideas:
                </p>
                <ul>
                    <li><strong>Community.</strong> Donation first, free for the people who need help.</li>
                    <li><strong>Privacy.</strong> Contact details and documents are never public.</li>
                    <li><strong>Safety.</strong> Eligibility rules, listing review, verification and reporting.</li>
                    <li><strong>Helpful AI.</strong> Semantic matching to find the same medicine under different names, without making medical decisions.</li>
                </ul>
                <h2>What this project is, and is not</h2>
                <p>
                    This is an <strong>academic prototype</strong>. It is not licensed or authorized to distribute medicines, and it should not
                    be used to exchange real medicines. Running a real service would require approval and oversight under Bangladesh
                    medicine regulations, which is outside the scope of a capstone project.
                </p>
                <h2>Sustainability</h2>
                <p>
                    The planned model is a social enterprise: partnerships with NGOs and hospitals, corporate social responsibility
                    sponsorship, and grants. Personal and prescription data is never sold, and basic assistance stays free.
                </p>
            </x-prose>

            <aside class="space-y-6">
                <x-card title="Project team">
                    <ul class="space-y-5">
                        @foreach (config('medimatch.team') as $member)
                            <li class="flex items-start gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white" aria-hidden="true">{{ mb_substr($member['name'], 0, 1) }}</span>
                                <div>
                                    <p class="text-sm font-semibold">{{ $member['name'] }}</p>
                                    <p class="text-sm text-brand-700">{{ $member['role'] }}</p>
                                    <p class="mt-0.5 text-sm text-ink-muted">{{ $member['focus'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-card>

                <x-card title="Built with">
                    <dl class="space-y-3 text-sm">
                        @foreach (['Web application' => 'Laravel, Blade, Tailwind CSS', 'Database' => 'MySQL', 'AI matching service' => 'Python, FastAPI, Sentence Transformers'] as $term => $value)
                            <div class="flex justify-between gap-4">
                                <dt class="text-ink-muted">{{ $term }}</dt>
                                <dd class="text-right font-medium">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-card>
            </aside>
        </div>
    </div>
</x-layouts.public>
