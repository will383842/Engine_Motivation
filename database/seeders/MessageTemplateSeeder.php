<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MessageTemplate;
use App\Models\MessageTemplateVariant;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = $this->getTemplates();

        foreach ($templates as $templateData) {
            $variants = $templateData['variants'];
            unset($templateData['variants']);

            $template = MessageTemplate::firstOrCreate(
                ['slug' => $templateData['slug']],
                $templateData
            );

            foreach ($variants as $variant) {
                MessageTemplateVariant::firstOrCreate(
                    [
                        'template_id' => $template->id,
                        'channel' => $variant['channel'],
                        'language' => $variant['language'],
                    ],
                    [
                        'subject' => $variant['subject'] ?? null,
                        'body' => $variant['body'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function getTemplates(): array
    {
        return [
            // ─── ONBOARDING: WELCOME ───
            [
                'slug' => 'onboarding_welcome',
                'name' => 'Onboarding - Welcome',
                'category' => 'onboarding',
                'is_active' => true,
                'variants' => $this->allLanguageVariants(
                    [
                        'fr' => "Bienvenue {name} ! 🎉\nTu fais maintenant partie de l'équipe SOS-Expat.\nPartage ton lien affilié et gagne des commissions sur chaque appel.\nTon lien : {affiliate_link}",
                        'en' => "Welcome {name}! 🎉\nYou're now part of the SOS-Expat team.\nShare your affiliate link and earn commissions on every call.\nYour link: {affiliate_link}",
                        'es' => "¡Bienvenido/a {name}! 🎉\nAhora eres parte del equipo SOS-Expat.\nComparte tu enlace de afiliado y gana comisiones por cada llamada.\nTu enlace: {affiliate_link}",
                        'de' => "Willkommen {name}! 🎉\nDu bist jetzt Teil des SOS-Expat Teams.\nTeile deinen Affiliate-Link und verdiene Provisionen für jeden Anruf.\nDein Link: {affiliate_link}",
                        'pt' => "Bem-vindo/a {name}! 🎉\nVocê agora faz parte da equipe SOS-Expat.\nCompartilhe seu link de afiliado e ganhe comissões em cada chamada.\nSeu link: {affiliate_link}",
                        'ru' => "Добро пожаловать, {name}! 🎉\nТы теперь часть команды SOS-Expat.\nДелись своей партнёрской ссылкой и зарабатывай комиссию с каждого звонка.\nТвоя ссылка: {affiliate_link}",
                        'zh' => "欢迎 {name}！🎉\n你现在是 SOS-Expat 团队的一员。\n分享你的推广链接，每次通话都能赚取佣金。\n你的链接：{affiliate_link}",
                        'hi' => "स्वागत है {name}! 🎉\nआप अब SOS-Expat टीम का हिस्सा हैं।\nअपना एफिलिएट लिंक शेयर करें और हर कॉल पर कमीशन कमाएं।\nआपका लिंक: {affiliate_link}",
                        'ar' => "مرحبًا {name}! 🎉\nأنت الآن جزء من فريق SOS-Expat.\nشارك رابط الإحالة الخاص بك واكسب عمولات على كل مكالمة.\nرابطك: {affiliate_link}",
                    ]
                ),
            ],

            // ─── ONBOARDING: TELEGRAM INVITE ───
            [
                'slug' => 'onboarding_telegram_invite',
                'name' => 'Onboarding - Telegram Invite',
                'category' => 'onboarding',
                'is_active' => true,
                'variants' => $this->allLanguageVariants(
                    [
                        'fr' => "💬 {name}, connecte ton Telegram pour recevoir tes notifications en temps réel !\nC'est gratuit et plus rapide.\n👉 {telegram_link}",
                        'en' => "💬 {name}, connect your Telegram to get real-time notifications!\nIt's free and faster.\n👉 {telegram_link}",
                        'es' => "💬 {name}, conecta tu Telegram para recibir notificaciones en tiempo real.\n¡Es gratis y más rápido!\n👉 {telegram_link}",
                        'de' => "💬 {name}, verbinde dein Telegram für Echtzeit-Benachrichtigungen!\nKostenlos und schneller.\n👉 {telegram_link}",
                        'pt' => "💬 {name}, conecte seu Telegram para receber notificações em tempo real!\nÉ grátis e mais rápido.\n👉 {telegram_link}",
                        'ru' => "💬 {name}, подключи Telegram для мгновенных уведомлений!\nЭто бесплатно и быстрее.\n👉 {telegram_link}",
                        'zh' => "💬 {name}，连接你的 Telegram 获取实时通知！\n免费且更快。\n👉 {telegram_link}",
                        'hi' => "💬 {name}, रियल-टाइम नोटिफिकेशन के लिए अपना Telegram कनेक्ट करें!\nयह मुफ्त और तेज़ है।\n👉 {telegram_link}",
                        'ar' => "💬 {name}، اربط حسابك على Telegram لتلقي الإشعارات فورياً!\nمجاني وأسرع.\n👉 {telegram_link}",
                    ]
                ),
            ],

            // ─── FIRST SALE CELEBRATION ───
            [
                'slug' => 'first_sale_celebration',
                'name' => 'First Sale Celebration',
                'category' => 'celebration',
                'is_active' => true,
                'variants' => $this->allLanguageVariants(
                    [
                        'fr' => "🎊 BRAVO {name} !\nTa première vente vient d'être validée !\n💰 Commission : {amount}\nContinue comme ça, tu es sur la bonne voie ! 🚀",
                        'en' => "🎊 CONGRATULATIONS {name}!\nYour first sale has been validated!\n💰 Commission: {amount}\nKeep it up, you're on the right track! 🚀",
                        'es' => "🎊 ¡FELICIDADES {name}!\n¡Tu primera venta ha sido validada!\n💰 Comisión: {amount}\n¡Sigue así, vas por buen camino! 🚀",
                        'de' => "🎊 GLÜCKWUNSCH {name}!\nDein erster Verkauf wurde bestätigt!\n💰 Provision: {amount}\nWeiter so, du bist auf dem richtigen Weg! 🚀",
                        'pt' => "🎊 PARABÉNS {name}!\nSua primeira venda foi validada!\n💰 Comissão: {amount}\nContinue assim, você está no caminho certo! 🚀",
                        'ru' => "🎊 ПОЗДРАВЛЯЕМ, {name}!\nТвоя первая продажа подтверждена!\n💰 Комиссия: {amount}\nТак держать, ты на правильном пути! 🚀",
                        'zh' => "🎊 恭喜 {name}！\n你的首次销售已确认！\n💰 佣金：{amount}\n继续加油，你走在正确的道路上！🚀",
                        'hi' => "🎊 बधाई हो {name}!\nआपकी पहली सेल वैलिडेट हो गई!\n💰 कमीशन: {amount}\nइसी तरह करते रहें, आप सही रास्ते पर हैं! 🚀",
                        'ar' => "🎊 مبروك {name}!\nتم التحقق من أول عملية بيع لك!\n💰 العمولة: {amount}\nواصل هكذا، أنت على الطريق الصحيح! 🚀",
                    ]
                ),
            ],

            // ─── STREAK BROKEN ───
            [
                'slug' => 'streak_broken',
                'name' => 'Streak Broken',
                'category' => 'gamification',
                'is_active' => true,
                'variants' => $this->allLanguageVariants(
                    [
                        'fr' => "😔 {name}, ton streak de {streak_days} jours s'est arrêté.\nMais pas de panique ! Chaque jour est un nouveau départ.\nRecommence maintenant et bats ton record ! 💪",
                        'en' => "😔 {name}, your {streak_days}-day streak has ended.\nBut don't worry! Every day is a fresh start.\nGet back on track now and beat your record! 💪",
                        'es' => "😔 {name}, tu racha de {streak_days} días se ha roto.\n¡Pero no te preocupes! Cada día es un nuevo comienzo.\n¡Vuelve ahora y bate tu récord! 💪",
                        'de' => "😔 {name}, deine {streak_days}-Tage-Serie ist vorbei.\nAber keine Sorge! Jeder Tag ist ein neuer Anfang.\nFang jetzt wieder an und brich deinen Rekord! 💪",
                        'pt' => "😔 {name}, sua sequência de {streak_days} dias acabou.\nMas não se preocupe! Cada dia é um novo começo.\nVolte agora e bata seu recorde! 💪",
                        'ru' => "😔 {name}, твоя серия из {streak_days} дней прервалась.\nНо не переживай! Каждый день — новое начало.\nНачни снова и побей свой рекорд! 💪",
                        'zh' => "😔 {name}，你的 {streak_days} 天连续记录中断了。\n但别担心！每一天都是新的开始。\n现在重新开始，打破你的记录！💪",
                        'hi' => "😔 {name}, आपकी {streak_days} दिन की स्ट्रीक टूट गई।\nलेकिन चिंता मत करें! हर दिन एक नई शुरुआत है।\nअभी वापस आएं और अपना रिकॉर्ड तोड़ें! 💪",
                        'ar' => "😔 {name}، انتهت سلسلتك المتواصلة منذ {streak_days} يوم.\nلكن لا تقلق! كل يوم هو بداية جديدة.\nعُد الآن وحطّم رقمك القياسي! 💪",
                    ]
                ),
            ],

            // ─── STREAK MILESTONE ───
            [
                'slug' => 'streak_milestone',
                'name' => 'Streak Milestone',
                'category' => 'gamification',
                'is_active' => true,
                'variants' => $this->allLanguageVariants(
                    [
                        'fr' => "🔥 {name}, {streak_days} jours de streak consécutifs !\nTu es en feu ! Continue comme ça.\n🏅 +{xp_bonus} XP bonus !",
                        'en' => "🔥 {name}, {streak_days} consecutive streak days!\nYou're on fire! Keep it going.\n🏅 +{xp_bonus} bonus XP!",
                        'es' => "🔥 {name}, ¡{streak_days} días consecutivos de racha!\n¡Estás que ardes! Sigue así.\n🏅 +{xp_bonus} XP de bonificación!",
                        'de' => "🔥 {name}, {streak_days} Tage Serie am Stück!\nDu bist on fire! Weiter so.\n🏅 +{xp_bonus} Bonus-XP!",
                        'pt' => "🔥 {name}, {streak_days} dias consecutivos de sequência!\nVocê está arrasando! Continue assim.\n🏅 +{xp_bonus} XP de bônus!",
                        'ru' => "🔥 {name}, {streak_days} дней подряд!\nТы в ударе! Продолжай.\n🏅 +{xp_bonus} бонусных XP!",
                        'zh' => "🔥 {name}，连续 {streak_days} 天打卡！\n你太厉害了！继续保持。\n🏅 +{xp_bonus} 奖励经验值！",
                        'hi' => "🔥 {name}, लगातार {streak_days} दिन की स्ट्रीक!\nआप शानदार हैं! ऐसे ही जारी रखें।\n🏅 +{xp_bonus} बोनस XP!",
                        'ar' => "🔥 {name}، {streak_days} يوم متواصل!\nأنت رائع! واصل هكذا.\n🏅 +{xp_bonus} نقاط خبرة إضافية!",
                    ]
                ),
            ],

            // ─── LEVEL UP ───
            [
                'slug' => 'level_up',
                'name' => 'Level Up',
                'category' => 'gamification',
                'is_active' => true,
                'variants' => $this->allLanguageVariants(
                    [
                        'fr' => "⬆️ {name}, tu passes au niveau {level} !\nNouveau titre : {title}\n{total_xp} XP au total. Continue ta montée ! 🌟",
                        'en' => "⬆️ {name}, you've reached level {level}!\nNew title: {title}\n{total_xp} total XP. Keep climbing! 🌟",
                        'es' => "⬆️ {name}, ¡alcanzaste el nivel {level}!\nNuevo título: {title}\n{total_xp} XP en total. ¡Sigue subiendo! 🌟",
                        'de' => "⬆️ {name}, du hast Level {level} erreicht!\nNeuer Titel: {title}\n{total_xp} XP insgesamt. Weiter nach oben! 🌟",
                        'pt' => "⬆️ {name}, você alcançou o nível {level}!\nNovo título: {title}\n{total_xp} XP no total. Continue subindo! 🌟",
                        'ru' => "⬆️ {name}, ты достиг уровня {level}!\nНовый титул: {title}\n{total_xp} XP всего. Продолжай расти! 🌟",
                        'zh' => "⬆️ {name}，你升到了第 {level} 级！\n新头衔：{title}\n总计 {total_xp} 经验值。继续攀升！🌟",
                        'hi' => "⬆️ {name}, आप लेवल {level} पर पहुंच गए!\nनई उपाधि: {title}\nकुल {total_xp} XP। ऊपर चढ़ते रहें! 🌟",
                        'ar' => "⬆️ {name}، وصلت إلى المستوى {level}!\nلقب جديد: {title}\n{total_xp} نقطة خبرة إجمالية. واصل الصعود! 🌟",
                    ]
                ),
            ],

            // ─── BADGE EARNED ───
            [
                'slug' => 'badge_earned',
                'name' => 'Badge Earned',
                'category' => 'gamification',
                'is_active' => true,
                'variants' => $this->allLanguageVariants(
                    [
                        'fr' => "🏆 Nouveau badge débloqué !\n{name}, tu as obtenu : {badge_name}\n+{xp_reward} XP",
                        'en' => "🏆 New badge unlocked!\n{name}, you've earned: {badge_name}\n+{xp_reward} XP",
                        'es' => "🏆 ¡Nueva insignia desbloqueada!\n{name}, has ganado: {badge_name}\n+{xp_reward} XP",
                        'de' => "🏆 Neues Abzeichen freigeschaltet!\n{name}, du hast erhalten: {badge_name}\n+{xp_reward} XP",
                        'pt' => "🏆 Novo emblema desbloqueado!\n{name}, você ganhou: {badge_name}\n+{xp_reward} XP",
                        'ru' => "🏆 Новый значок разблокирован!\n{name}, ты получил: {badge_name}\n+{xp_reward} XP",
                        'zh' => "🏆 解锁新徽章！\n{name}，你获得了：{badge_name}\n+{xp_reward} 经验值",
                        'hi' => "🏆 नया बैज अनलॉक!\n{name}, आपने कमाया: {badge_name}\n+{xp_reward} XP",
                        'ar' => "🏆 شارة جديدة!\n{name}، حصلت على: {badge_name}\n+{xp_reward} نقطة خبرة",
                    ]
                ),
            ],

            // ─── MISSION COMPLETED ───
            [
                'slug' => 'mission_completed',
                'name' => 'Mission Completed',
                'category' => 'gamification',
                'is_active' => true,
                'variants' => $this->allLanguageVariants(
                    [
                        'fr' => "✅ Mission accomplie !\n{name}, tu as terminé : {mission_name}\n+{xp_reward} XP 🎯",
                        'en' => "✅ Mission complete!\n{name}, you've finished: {mission_name}\n+{xp_reward} XP 🎯",
                        'es' => "✅ ¡Misión cumplida!\n{name}, completaste: {mission_name}\n+{xp_reward} XP 🎯",
                        'de' => "✅ Mission abgeschlossen!\n{name}, du hast erledigt: {mission_name}\n+{xp_reward} XP 🎯",
                        'pt' => "✅ Missão cumprida!\n{name}, você completou: {mission_name}\n+{xp_reward} XP 🎯",
                        'ru' => "✅ Миссия выполнена!\n{name}, ты завершил: {mission_name}\n+{xp_reward} XP 🎯",
                        'zh' => "✅ 任务完成！\n{name}，你完成了：{mission_name}\n+{xp_reward} 经验值 🎯",
                        'hi' => "✅ मिशन पूरा!\n{name}, आपने पूरा किया: {mission_name}\n+{xp_reward} XP 🎯",
                        'ar' => "✅ المهمة مكتملة!\n{name}، أتممت: {mission_name}\n+{xp_reward} نقطة خبرة 🎯",
                    ]
                ),
            ],

            // ─── WEEKLY RECAP ───
            [
                'slug' => 'weekly_recap',
                'name' => 'Weekly Recap',
                'category' => 'engagement',
                'is_active' => true,
                'variants' => $this->allLanguageVariants(
                    [
                        'fr' => "📊 Récap semaine — {name}\n🏷️ Ventes : {sales_count}\n💰 Gains : {earnings}\n🔥 Streak : {streak_days} jours\n📈 Classement : #{rank}\nContinue cette semaine ! 💪",
                        'en' => "📊 Weekly recap — {name}\n🏷️ Sales: {sales_count}\n💰 Earnings: {earnings}\n🔥 Streak: {streak_days} days\n📈 Rank: #{rank}\nKeep it up this week! 💪",
                        'es' => "📊 Resumen semanal — {name}\n🏷️ Ventas: {sales_count}\n💰 Ganancias: {earnings}\n🔥 Racha: {streak_days} días\n📈 Ranking: #{rank}\n¡Sigue esta semana! 💪",
                        'de' => "📊 Wochenrückblick — {name}\n🏷️ Verkäufe: {sales_count}\n💰 Verdienst: {earnings}\n🔥 Serie: {streak_days} Tage\n📈 Rang: #{rank}\nWeiter so diese Woche! 💪",
                        'pt' => "📊 Resumo semanal — {name}\n🏷️ Vendas: {sales_count}\n💰 Ganhos: {earnings}\n🔥 Sequência: {streak_days} dias\n📈 Ranking: #{rank}\nContinue nesta semana! 💪",
                        'ru' => "📊 Итоги недели — {name}\n🏷️ Продажи: {sales_count}\n💰 Доход: {earnings}\n🔥 Серия: {streak_days} дней\n📈 Рейтинг: #{rank}\nТак держать на этой неделе! 💪",
                        'zh' => "📊 每周总结 — {name}\n🏷️ 销售：{sales_count}\n💰 收入：{earnings}\n🔥 连续：{streak_days} 天\n📈 排名：#{rank}\n这周继续加油！💪",
                        'hi' => "📊 साप्ताहिक रिकैप — {name}\n🏷️ बिक्री: {sales_count}\n💰 कमाई: {earnings}\n🔥 स्ट्रीक: {streak_days} दिन\n📈 रैंक: #{rank}\nइस हफ्ते भी जारी रखें! 💪",
                        'ar' => "📊 ملخص الأسبوع — {name}\n🏷️ المبيعات: {sales_count}\n💰 الأرباح: {earnings}\n🔥 السلسلة: {streak_days} يوم\n📈 الترتيب: #{rank}\nواصل هذا الأسبوع! 💪",
                    ]
                ),
            ],

            // ─── REACTIVATION 7 DAYS ───
            [
                'slug' => 'reactivation_7d',
                'name' => 'Reactivation 7 Days',
                'category' => 'reactivation',
                'is_active' => true,
                'variants' => $this->allLanguageVariants(
                    [
                        'fr' => "👋 {name}, ça fait 7 jours !\nTon lien affilié t'attend toujours.\nUne seule vente peut tout relancer.\n🔗 {affiliate_link}",
                        'en' => "👋 {name}, it's been 7 days!\nYour affiliate link is still waiting.\nOne sale can restart everything.\n🔗 {affiliate_link}",
                        'es' => "👋 {name}, ¡han pasado 7 días!\nTu enlace de afiliado te sigue esperando.\nUna venta puede reiniciar todo.\n🔗 {affiliate_link}",
                        'de' => "👋 {name}, 7 Tage sind vergangen!\nDein Affiliate-Link wartet noch.\nEin Verkauf kann alles neu starten.\n🔗 {affiliate_link}",
                        'pt' => "👋 {name}, já se passaram 7 dias!\nSeu link de afiliado ainda está esperando.\nUma venda pode reiniciar tudo.\n🔗 {affiliate_link}",
                        'ru' => "👋 {name}, прошло 7 дней!\nТвоя партнёрская ссылка ждёт.\nОдна продажа может всё перезапустить.\n🔗 {affiliate_link}",
                        'zh' => "👋 {name}，已经7天了！\n你的推广链接还在等你。\n一笔销售就能重新开始。\n🔗 {affiliate_link}",
                        'hi' => "👋 {name}, 7 दिन हो गए!\nआपका एफिलिएट लिंक अभी भी इंतज़ार कर रहा है।\nएक सेल सब कुछ फिर से शुरू कर सकती है।\n🔗 {affiliate_link}",
                        'ar' => "👋 {name}، مرت 7 أيام!\nرابط الإحالة الخاص بك لا يزال ينتظر.\nعملية بيع واحدة يمكن أن تعيد كل شيء.\n🔗 {affiliate_link}",
                    ]
                ),
            ],

            // ─── REACTIVATION 14 DAYS ───
            [
                'slug' => 'reactivation_14d',
                'name' => 'Reactivation 14 Days',
                'category' => 'reactivation',
                'is_active' => true,
                'variants' => $this->allLanguageVariants(
                    [
                        'fr' => "🫤 {name}, tu nous manques !\n14 jours sans activité.\nPendant ce temps, d'autres chatters ont gagné {top_earnings} cette semaine.\nReviens et reprends ta place ! 🔗 {affiliate_link}",
                        'en' => "🫤 {name}, we miss you!\n14 days without activity.\nMeanwhile, other chatters earned {top_earnings} this week.\nCome back and reclaim your spot! 🔗 {affiliate_link}",
                        'es' => "🫤 {name}, ¡te echamos de menos!\n14 días sin actividad.\nMientras tanto, otros chatters ganaron {top_earnings} esta semana.\n¡Regresa y reclama tu lugar! 🔗 {affiliate_link}",
                        'de' => "🫤 {name}, wir vermissen dich!\n14 Tage ohne Aktivität.\nAndere Chatters haben diese Woche {top_earnings} verdient.\nKomm zurück und hol dir deinen Platz! 🔗 {affiliate_link}",
                        'pt' => "🫤 {name}, sentimos sua falta!\n14 dias sem atividade.\nEnquanto isso, outros chatters ganharam {top_earnings} esta semana.\nVolte e retome sua posição! 🔗 {affiliate_link}",
                        'ru' => "🫤 {name}, мы скучаем!\n14 дней без активности.\nДругие чаттеры заработали {top_earnings} на этой неделе.\nВернись и займи своё место! 🔗 {affiliate_link}",
                        'zh' => "🫤 {name}，我们想你了！\n14天没有活动。\n与此同时，其他聊天者本周赚了 {top_earnings}。\n回来夺回你的位置！🔗 {affiliate_link}",
                        'hi' => "🫤 {name}, हम आपकी कमी महसूस कर रहे हैं!\n14 दिन बिना गतिविधि।\nइस बीच, अन्य चैटर्स ने इस हफ्ते {top_earnings} कमाए।\nवापस आएं और अपनी जगह फिर से लें! 🔗 {affiliate_link}",
                        'ar' => "🫤 {name}، نفتقدك!\n14 يوم بدون نشاط.\nفي هذه الأثناء، كسب آخرون {top_earnings} هذا الأسبوع.\nعُد واستعد مكانك! 🔗 {affiliate_link}",
                    ]
                ),
            ],

            // ─── FLASH BONUS ───
            [
                'slug' => 'flash_bonus',
                'name' => 'Flash Bonus',
                'category' => 'promotion',
                'is_active' => true,
                'variants' => $this->allLanguageVariants(
                    [
                        'fr' => "⚡ BONUS FLASH !\n{name}, gagne {bonus_amount} de bonus si tu fais {target} vente(s) dans les prochaines {hours}h !\n⏰ Expire à {expiry_time}\n🔗 {affiliate_link}",
                        'en' => "⚡ FLASH BONUS!\n{name}, earn {bonus_amount} bonus if you make {target} sale(s) in the next {hours}h!\n⏰ Expires at {expiry_time}\n🔗 {affiliate_link}",
                        'es' => "⚡ ¡BONUS FLASH!\n{name}, gana {bonus_amount} de bonificación si haces {target} venta(s) en las próximas {hours}h.\n⏰ Expira a las {expiry_time}\n🔗 {affiliate_link}",
                        'de' => "⚡ FLASH BONUS!\n{name}, verdiene {bonus_amount} Bonus bei {target} Verkauf/-en in den nächsten {hours}h!\n⏰ Läuft ab um {expiry_time}\n🔗 {affiliate_link}",
                        'pt' => "⚡ BÔNUS FLASH!\n{name}, ganhe {bonus_amount} de bônus com {target} venda(s) nas próximas {hours}h!\n⏰ Expira às {expiry_time}\n🔗 {affiliate_link}",
                        'ru' => "⚡ ФЛЭШ-БОНУС!\n{name}, получи бонус {bonus_amount} за {target} продаж(и) в течение {hours}ч!\n⏰ Истекает в {expiry_time}\n🔗 {affiliate_link}",
                        'zh' => "⚡ 限时奖励！\n{name}，在接下来 {hours} 小时内完成 {target} 笔销售，赚取 {bonus_amount} 奖金！\n⏰ 截止时间：{expiry_time}\n🔗 {affiliate_link}",
                        'hi' => "⚡ फ्लैश बोनस!\n{name}, अगले {hours} घंटों में {target} सेल करके {bonus_amount} बोनस कमाएं!\n⏰ समाप्ति: {expiry_time}\n🔗 {affiliate_link}",
                        'ar' => "⚡ بونص فلاش!\n{name}، اكسب {bonus_amount} بونص إذا أجريت {target} عملية بيع خلال {hours} ساعة!\n⏰ ينتهي في {expiry_time}\n🔗 {affiliate_link}",
                    ]
                ),
            ],

            // ─── SALE COMPLETED ───
            [
                'slug' => 'sale_completed',
                'name' => 'Sale Completed',
                'category' => 'transactional',
                'is_active' => true,
                'variants' => $this->allLanguageVariants(
                    [
                        'fr' => "💰 Nouvelle vente !\n{name}, une commission de {amount} vient d'être créditée.\n📊 Total ce mois : {monthly_total} ({monthly_sales} ventes)",
                        'en' => "💰 New sale!\n{name}, a {amount} commission has been credited.\n📊 This month: {monthly_total} ({monthly_sales} sales)",
                        'es' => "💰 ¡Nueva venta!\n{name}, se ha acreditado una comisión de {amount}.\n📊 Este mes: {monthly_total} ({monthly_sales} ventas)",
                        'de' => "💰 Neuer Verkauf!\n{name}, eine Provision von {amount} wurde gutgeschrieben.\n📊 Diesen Monat: {monthly_total} ({monthly_sales} Verkäufe)",
                        'pt' => "💰 Nova venda!\n{name}, uma comissão de {amount} foi creditada.\n📊 Este mês: {monthly_total} ({monthly_sales} vendas)",
                        'ru' => "💰 Новая продажа!\n{name}, комиссия {amount} зачислена.\n📊 В этом месяце: {monthly_total} ({monthly_sales} продаж)",
                        'zh' => "💰 新销售！\n{name}，{amount} 佣金已入账。\n📊 本月：{monthly_total}（{monthly_sales} 笔销售）",
                        'hi' => "💰 नई सेल!\n{name}, {amount} कमीशन क्रेडिट हो गया।\n📊 इस महीने: {monthly_total} ({monthly_sales} सेल्स)",
                        'ar' => "💰 عملية بيع جديدة!\n{name}، تم إضافة عمولة {amount}.\n📊 هذا الشهر: {monthly_total} ({monthly_sales} عملية بيع)",
                    ]
                ),
            ],

            // ─── LEADERBOARD UPDATE ───
            [
                'slug' => 'leaderboard_update',
                'name' => 'Leaderboard Position Update',
                'category' => 'gamification',
                'is_active' => true,
                'variants' => $this->allLanguageVariants(
                    [
                        'fr' => "🏆 {name}, tu es #{rank} au classement cette semaine !\n{message}\nContinue pour garder ta place ! 📈",
                        'en' => "🏆 {name}, you're #{rank} on the leaderboard this week!\n{message}\nKeep going to hold your spot! 📈",
                        'es' => "🏆 {name}, ¡eres #{rank} en el ranking esta semana!\n{message}\n¡Sigue para mantener tu puesto! 📈",
                        'de' => "🏆 {name}, du bist #{rank} in der Rangliste diese Woche!\n{message}\nWeiter so! 📈",
                        'pt' => "🏆 {name}, você é #{rank} no ranking esta semana!\n{message}\nContinue para manter sua posição! 📈",
                        'ru' => "🏆 {name}, ты #{rank} в рейтинге на этой неделе!\n{message}\nПродолжай, чтобы удержать позицию! 📈",
                        'zh' => "🏆 {name}，你本周排名第 #{rank}！\n{message}\n继续努力保持你的位置！📈",
                        'hi' => "🏆 {name}, आप इस हफ्ते लीडरबोर्ड पर #{rank} हैं!\n{message}\nअपनी जगह बनाए रखने के लिए जारी रखें! 📈",
                        'ar' => "🏆 {name}، أنت رقم #{rank} في التصنيف هذا الأسبوع!\n{message}\nواصل للحفاظ على مركزك! 📈",
                    ]
                ),
            ],
        ];
    }

    /**
     * Generate variants for both telegram and whatsapp channels in all 9 languages.
     */
    private function allLanguageVariants(array $messagesByLang): array
    {
        $variants = [];
        $channels = ['telegram', 'whatsapp'];

        foreach ($channels as $channel) {
            foreach ($messagesByLang as $lang => $body) {
                $variants[] = [
                    'channel' => $channel,
                    'language' => $lang,
                    'body' => $body,
                ];
            }
        }

        return $variants;
    }
}
