<?php
/**
 * Universal Tenant Footer
 * Centralizes company info, quick links, and social links for every tenant.
 */
$tenant = getTenant();
$tenant_id = getTenantId();
?>

<footer class="bg-gray-900 text-white py-16 mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-4 gap-12 text-center md:text-left">
            <!-- Company Info -->
            <div class="md:col-span-1">
                <div class="flex items-center justify-center md:justify-start space-x-3 mb-6">
                    <?php if (!empty($tenant['logo_url'])): ?>
                    <img src="<?= htmlspecialchars($tenant['logo_url'])?>" alt="Logo"
                        class="h-8 w-auto grayscale brightness-200 opacity-60">
                    <?php
else: ?>
                    <div class="w-8 h-8 bg-white/10 rounded flex items-center justify-center text-white/50 font-black">⚡
                    </div>
                    <?php
endif; ?>
                    <span class="text-xl font-black italic tracking-tighter">
                        <?= htmlspecialchars($content['company_name'] ?? $tenant['name'])?>
                    </span>
                </div>
                <p class="text-gray-400 text-sm leading-relaxed max-w-xs mx-auto md:mx-0 font-medium">
                    Providing premium vehicle solutions for modern mobility. Quality cars, transparent pricing, and
                    exceptional service.
                </p>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-xs font-black text-white/30 uppercase tracking-[0.2em] mb-8">Navigation</h4>
                <ul class="space-y-4 text-sm font-bold text-gray-400">
                    <li><a href="<?= $tenant_home?>" class="hover:text-white transition">Home</a></li>
                    <li><a href="<?= $fleet_url?>" class="hover:text-white transition">Our Fleet</a></li>
                    <li><a href="<?= $tenant_home?>#about" class="hover:text-white transition">About Us</a></li>
                    <li><a href="<?= $tenant_home?>#contact" class="hover:text-white transition">Contact</a></li>
                </ul>
            </div>

            <!-- Services -->
            <div>
                <h4 class="text-xs font-black text-white/30 uppercase tracking-[0.2em] mb-8">Offerings</h4>
                <ul class="space-y-4 text-sm font-bold text-gray-400">
                    <li><a href="<?= $fleet_url?>?type=luxury"
                            class="hover:text-white transition uppercase text-[10px] tracking-widest">Luxury Rental</a>
                    </li>
                    <li><a href="<?= $fleet_url?>?type=standard"
                            class="hover:text-white transition uppercase text-[10px] tracking-widest">Standard Daily</a>
                    </li>
                    <li><a href="<?= $fleet_url?>?type=electric"
                            class="hover:text-white transition uppercase text-[10px] tracking-widest">EV Fleet</a></li>
                    <li><a href="<?= $fleet_url?>?type=van"
                            class="hover:text-white transition uppercase text-[10px] tracking-widest">Commercial
                            Vans</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div>
                <h4 class="text-xs font-black text-white/30 uppercase tracking-[0.2em] mb-8">Get In Touch</h4>
                <p class="text-gray-400 text-sm mb-4 font-bold">
                    <?= htmlspecialchars($content['contact_phone'] ?? '+1 (555) 123-4567')?>
                </p>
                <p class="text-gray-400 text-sm mb-8 font-bold">
                    <?= htmlspecialchars($content['contact_email'] ?? 'info@fleet-rental.com')?>
                </p>
                <p class="text-[10px] text-gray-600 font-bold leading-relaxed uppercase tracking-widest">
                    Available 24/7 for booking support and roadside assistance.
                </p>
            </div>
        </div>

        <div class="border-t border-white/5 mt-16 pt-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <p class="text-[11px] text-gray-600 font-black uppercase tracking-[0.2em]">&copy;
                <?= date('Y')?>
                <?= htmlspecialchars($content['company_name'] ?? $tenant['name'])?>. Built with <a target="_blank"
                    href="https://www.fleetrentalpro.com"><span class="text-blue-500/50">FleetRentalPro</span></a>
            </p>
            <div class="flex items-center gap-6 text-gray-600">
                <a href="#"
                    class="text-[10px] font-black uppercase tracking-widest hover:text-white transition">Privacy</a>
                <a href="#"
                    class="text-[10px] font-black uppercase tracking-widest hover:text-white transition">Terms</a>
            </div>
        </div>
    </div>
</footer>