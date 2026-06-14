<?php
/**
 * Universal Tenant Footer
 */
$tenant = getTenant();
$tenant_id = getTenantId();

$pdo = getDB();
$stmt = $pdo->prepare("SELECT company_phone, whatsapp_number, whatsapp_enabled FROM tenant_settings WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$ts = $stmt->fetch() ?: [];
$company_phone   = $ts['company_phone'] ?? '';
$whatsapp_number = $ts['whatsapp_number'] ?? '';
$whatsapp_enabled = !empty($ts['whatsapp_enabled']);

$footer_phone   = $company_phone ?: ($content['contact_phone'] ?? '');
$footer_email   = $content['contact_email'] ?? '';
$footer_address = $content['contact_address'] ?? '';
$company_name_f = $content['company_name'] ?? $tenant['name'];
$map_query      = urlencode($footer_address ?: $company_name_f);
?>

<footer class="bg-white border-t-4 border-gray-900 pt-14 pb-8 shadow-[0_-1px_0_0_#e5e7eb]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">

            <!-- Col 1: Logo + Newsletter + Social -->
            <div class="sm:col-span-2 lg:col-span-1">
                <div class="flex items-center gap-3 mb-4">
                    <?php if (!empty($tenant['logo_url'])): ?>
                    <img src="<?= htmlspecialchars($tenant['logo_url'])?>" alt="Logo" class="h-9 w-auto">
                    <?php else: ?>
                    <div class="w-9 h-9 rounded-lg bg-gray-900 flex items-center justify-center text-white font-black text-sm">⚡</div>
                    <?php endif; ?>
                    <span class="font-extrabold text-gray-900 text-lg tracking-tight"><?= htmlspecialchars($company_name_f)?></span>
                </div>
                <p class="text-xs text-gray-400 mb-1 uppercase tracking-widest font-semibold">Newsletter</p>
                <p class="text-sm text-gray-500 mb-3">Get offers &amp; updates straight to your inbox.</p>
                <div class="flex gap-2 mb-6 max-w-xs">
                    <input type="email" placeholder="yourmail@gmail.com"
                        class="flex-1 min-w-0 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent placeholder-gray-400">
                    <button class="flex-shrink-0 bg-gray-900 text-white px-4 py-2.5 rounded-lg text-sm font-bold hover:bg-gray-700 transition flex items-center gap-1.5">
                        Go
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
                <div class="flex gap-2.5">
                    <a href="#" aria-label="Instagram" class="w-8 h-8 rounded-full border border-gray-200 bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-gray-900 hover:text-white hover:border-gray-900 transition">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                    <a href="#" aria-label="Facebook" class="w-8 h-8 rounded-full border border-gray-200 bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-gray-900 hover:text-white hover:border-gray-900 transition">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="#" aria-label="X / Twitter" class="w-8 h-8 rounded-full border border-gray-200 bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-gray-900 hover:text-white hover:border-gray-900 transition">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                </div>
            </div>

            <!-- Col 2: Working hours + Contacts -->
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Working hours</p>
                <div class="space-y-2 text-sm mb-6">
                    <div class="flex items-center justify-between gap-2 py-1.5 border-b border-dashed border-gray-100">
                        <span class="text-gray-500">Mon – Fri</span>
                        <span class="font-semibold text-gray-800 text-xs whitespace-nowrap">08:00 – 20:00</span>
                    </div>
                    <div class="flex items-center justify-between gap-2 py-1.5 border-b border-dashed border-gray-100">
                        <span class="text-gray-500">Saturday</span>
                        <span class="font-semibold text-gray-800 text-xs whitespace-nowrap">09:00 – 18:00</span>
                    </div>
                    <div class="flex items-center justify-between gap-2 py-1.5">
                        <span class="text-gray-500">Sunday</span>
                        <span class="font-semibold text-gray-800 text-xs whitespace-nowrap">10:00 – 16:00</span>
                    </div>
                </div>

                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Contacts</p>
                <div class="space-y-2.5">
                    <?php if (!empty($footer_phone)): ?>
                    <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/','',$footer_phone))?>" class="flex items-center gap-2.5 text-sm text-gray-600 hover:text-gray-900 transition group">
                        <span class="w-7 h-7 rounded-lg bg-gray-100 group-hover:bg-gray-900 group-hover:text-white flex items-center justify-center transition flex-shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </span>
                        <?= htmlspecialchars($footer_phone)?>
                    </a>
                    <?php endif; ?>
                    <?php if ($whatsapp_enabled && !empty($whatsapp_number)): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $whatsapp_number)?>" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2.5 text-sm text-gray-600 hover:text-green-600 transition group">
                        <span class="w-7 h-7 rounded-lg bg-gray-100 group-hover:bg-green-500 group-hover:text-white flex items-center justify-center transition flex-shrink-0">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </span>
                        <?= htmlspecialchars($whatsapp_number)?> <span class="text-xs text-green-500 font-medium">WhatsApp</span>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($footer_email)): ?>
                    <a href="mailto:<?= htmlspecialchars($footer_email)?>" class="flex items-center gap-2.5 text-sm text-gray-600 hover:text-gray-900 transition group">
                        <span class="w-7 h-7 rounded-lg bg-gray-100 group-hover:bg-gray-900 group-hover:text-white flex items-center justify-center transition flex-shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </span>
                        <?= htmlspecialchars($footer_email)?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Col 3: Useful links + Our location -->
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Useful links</p>
                <ul class="space-y-2 text-sm mb-7">
                    <li><a href="#" class="flex items-center gap-2 text-gray-500 hover:text-gray-900 transition group"><svg class="w-3 h-3 text-gray-300 group-hover:text-gray-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>FAQs</a></li>
                    <li><a href="#" class="flex items-center gap-2 text-gray-500 hover:text-gray-900 transition group"><svg class="w-3 h-3 text-gray-300 group-hover:text-gray-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>Privacy policy</a></li>
                    <li><a href="#" class="flex items-center gap-2 text-gray-500 hover:text-gray-900 transition group"><svg class="w-3 h-3 text-gray-300 group-hover:text-gray-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>Terms &amp; conditions</a></li>
                    <li><a href="#" class="flex items-center gap-2 text-gray-500 hover:text-gray-900 transition group"><svg class="w-3 h-3 text-gray-300 group-hover:text-gray-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>Insurance Details</a></li>
                    <li><a href="#" class="flex items-center gap-2 text-gray-500 hover:text-gray-900 transition group"><svg class="w-3 h-3 text-gray-300 group-hover:text-gray-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>Rental agreement</a></li>
                </ul>
                <?php if (!empty($footer_address)): ?>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Our location</p>
                <div class="flex items-start gap-2 text-sm text-gray-500">
                    <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="leading-relaxed"><?= htmlspecialchars($footer_address)?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Col 4: Map -->
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Find us</p>
                <div class="rounded-xl overflow-hidden border border-gray-200 shadow-sm" style="height:210px;">
                    <iframe src="https://maps.google.com/maps?q=<?= $map_query?>&output=embed&z=14"
                        class="w-full h-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Our location"></iframe>
                </div>
                <?php if (!empty($footer_address)): ?>
                <a href="https://maps.google.com/maps?q=<?= $map_query?>" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-1.5 mt-2.5 text-xs font-semibold text-gray-500 hover:text-gray-900 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Get directions
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="border-t border-gray-100 pt-6 flex flex-col sm:flex-row justify-between items-center gap-3">
            <p class="text-xs text-gray-400">&copy; <?= date('Y')?> <span class="font-semibold text-gray-600"><?= htmlspecialchars($company_name_f)?></span>. All rights reserved.</p>
            <p class="text-xs text-gray-400">Built with <a href="https://www.fleetrentalpro.com" target="_blank" class="font-semibold text-blue-500 hover:text-blue-600 transition">FleetRentalPro</a></p>
        </div>
    </div>
</footer>

<?php if ($whatsapp_enabled && !empty($whatsapp_number)): ?>
<a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $whatsapp_number)?>" target="_blank" rel="noopener noreferrer"
   class="fixed bottom-6 right-6 z-[9999] flex items-center gap-2.5 bg-[#25D366] hover:bg-[#128C7E] text-white px-5 py-3.5 rounded-full shadow-2xl transition-all duration-300 hover:scale-105 font-bold text-sm tracking-wide">
    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"></path></svg>
    <span>Chat on WhatsApp</span>
</a>
<?php endif; ?>