<?php
/**
 * Universal Tenant Footer
 * Centralizes company info, quick links, and social links for every tenant.
 */
$tenant = getTenant();
$tenant_id = getTenantId();
?>

<footer class="bg-gray-900 text-white py-16 mt-20">
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
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

<!-- Floating WhatsApp Button -->
<a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $content['contact_phone'] ?? '1234567890')?>" target="_blank" rel="noopener noreferrer"
   class="fixed bottom-6 right-6 z-[9999] flex items-center gap-2.5 bg-[#25D366] hover:bg-[#128C7E] text-white px-5 py-3.5 rounded-full shadow-2xl transition-all duration-300 hover:scale-105 font-bold text-sm tracking-wide">
    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"></path>
    </svg>
    <span>Chat on WhatsApp</span>
</a>