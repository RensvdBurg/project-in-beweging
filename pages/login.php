<main class="min-h-screen flex items-center justify-center">

    <div class="phone-shell">

        <!-- Achtergrond -->
        <svg class="absolute inset-0 w-full h-full" viewBox="0 0 402 874" fill="none" xmlns="http://www.w3.org/2000/svg"
            preserveAspectRatio="none" aria-hidden="true">
            <rect width="402" height="874" fill="#F9FAFC" />
            <rect width="402" height="27" fill="#FF6B4A" />
            <path d="
                    M0 0H402V30
                    C367 29 345 47 323 88
                    C296 139 272 187 242 205
                    C211 224 174 213 145 213
                    C94 213 46 198 0 174V0Z
                " fill="#FF6B4A" />
            <path d="
                    M0 174
                    C46 198 94 213 145 213
                    C174 213 211 224 242 205
                    C272 187 296 139 323 88
                    C345 47 367 29 402 30
                " stroke="#D9DDE2" stroke-width="2" opacity=".8" />
            <path d="
                    M0 604
                    C28 628 58 645 96 649
                    C145 655 177 638 198 606
                    C222 569 242 522 265 486
                    C289 447 312 429 342 427
                    C365 425 385 419 402 405
                    V874H0V604Z
                " fill="#FF6B4A" />
            <path d="
                    M0 604
                    C28 628 58 645 96 649
                    C145 655 177 638 198 606
                    C222 569 242 522 265 486
                    C289 447 312 429 342 427
                    C365 425 385 419 402 405
                " stroke="#D9DDE2" stroke-width="2" opacity=".8" />
        </svg>

        <!-- Logo -->
        <div class="absolute top-[64px] left-[53px] z-20 flex items-center gap-1.5">

            <div
                class="flex h-[38px] w-[38px] items-center justify-center rounded-[10px] bg-white shadow-[0_6px_18px_rgba(0,0,0,.13)]">

                <svg width="26" height="26" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8.88826 17.5392C9.02181 17.6231 9.1764 17.6673 9.33408 17.6668H15.1668C15.3015 17.6663 15.4344 17.6986 15.554 17.7608C15.6736 17.823 15.7763 17.9133 15.8533 18.0239C15.9303 18.1346 15.9793 18.2622 15.9962 18.396C16.013 18.5297 15.9972 18.6656 15.95 18.7918L14.3502 23.8088C14.3251 23.8999 14.3318 23.9969 14.3691 24.0837C14.4064 24.1706 14.4721 24.2422 14.5554 24.2867C14.6387 24.3313 14.7347 24.3462 14.8276 24.3291C14.9206 24.3119 15.0049 24.2636 15.0668 24.1922L23.3158 15.6917C23.4152 15.5692 23.4777 15.421 23.4962 15.2644C23.5147 15.1078 23.4884 14.9491 23.4204 14.8069C23.3523 14.6646 23.2453 14.5446 23.1117 14.4608C22.9782 14.3769 22.8236 14.3327 22.6659 14.3332H16.8332C16.6985 14.3337 16.5656 14.3014 16.446 14.2392C16.3264 14.177 16.2237 14.0867 16.1467 13.9761C16.0697 13.8654 16.0207 13.7378 16.0038 13.604C15.987 13.4703 16.0028 13.3344 16.05 13.2082L17.6498 8.1912C17.6749 8.10007 17.6682 8.00312 17.6309 7.91628C17.5936 7.82943 17.5279 7.75784 17.4446 7.71326C17.3613 7.66868 17.2653 7.65376 17.1724 7.67095C17.0794 7.68814 16.9951 7.73641 16.9332 7.80785L8.68416 16.3084C8.58482 16.4308 8.52226 16.579 8.50376 16.7356C8.48526 16.8922 8.51156 17.0509 8.57963 17.1931C8.64769 17.3354 8.75471 17.4554 8.88826 17.5392Z"
                        stroke="#2EC4B6" stroke-width="2" stroke-linecap="round" />
                </svg>

            </div>

            <span class=" text-[17px] font-extrabold tracking-[-.4px] text-[#1A1C1E]">
                UpMove
            </span>

        </div>

        <!-- Login -->
        <section class="
            absolute
            left-[42px]
            right-[42px]
            top-[212px]
            z-10
            rounded-[20px]
            bg-white
            px-[26px]
            pt-[34px]
            pb-[38px]
            shadow-[0_8px_24px_rgba(26,28,30,.16)]">

            <h1 class="
                mb-[13px]
                text-[17px]
                font-semibold
                tracking-[-.2px]
                text-[#77787C]
            ">
                Aanmelden
            </h1>

            <form class="space-y-[26px]" action="#" method="POST">

                <!-- Gebruikersnaam -->
                <div>
                    <label for="username" class="sr-only">
                        Gebruikersnaam/email
                    </label>

                    <input id="username" name="username" type="text" autocomplete="username"
                        placeholder="Gebruikersnaam/email" class="
                            h-[48px]
                            w-full
                            rounded-[16px]
                            border
                            border-[#F0F0F1]
                            bg-white
                            px-[16px]
                            text-[12px]
                            text-[#1A1C1E]
                            outline-none
                            shadow-[0_6px_18px_rgba(26,28,30,.12)]
                            transition
                            focus:border-[#FF6B4A]
                            focus:ring-2
                            focus:ring-[#FF6B4A]/15
                        ">
                </div>

                <!-- Wachtwoord -->
                <div>
                    <label for="password" class="sr-only">
                        Wachtwoord
                    </label>

                    <input id="password" name="password" type="password" autocomplete="current-password"
                        placeholder="Wachtwoord" class="
                            h-[48px]
                            w-full
                            rounded-[16px]
                            border
                            border-[#F0F0F1]
                            bg-white
                            px-[16px]
                            text-[12px]
                            text-[#1A1C1E]
                            outline-none
                            shadow-[0_6px_18px_rgba(26,28,30,.12)]
                            transition
                            focus:border-[#FF6B4A]
                            focus:ring-2
                            focus:ring-[#FF6B4A]/15
                        ">
                </div>

                <!-- Button -->
                <button type="submit" class="
                        h-[32px]
                        w-full
                        rounded-full
                        bg-[#FF6B4A]
                        text-[11px]
                        font-semibold
                        text-white
                        shadow-[0_5px_12px_rgba(255,107,74,.28)]
                        transition
                        hover:brightness-95
                        active:scale-[.99]
                    ">
                    Aanmelden
                </button>

            </form>

            <p class="
                mt-[11px]
                whitespace-nowrap
                text-center
                text-[9px]
                text-[#DEDEE2]
            ">
                Nog geen account?

                <a href="#" class="font-semibold text-[#8B6FE8] hover:underline">
                    Account aanmaken
                </a>
            </p>

        </section>

    </div>

</main>