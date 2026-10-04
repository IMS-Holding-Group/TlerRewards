/**
 * حماية صارمة من البوتات - JavaScript
 * منع البوتات من الوصول إلى صفحات الإدارة
 */

(function() {
    'use strict';
    
    // التحقق من أن المتصفح يدعم JavaScript بشكل كامل
    const botDetection = {
        // قائمة User-Agents للبوتات
        botPatterns: [
            'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
            'yandexbot', 'sogou', 'exabot', 'facebot', 'ia_archiver',
            'curl', 'wget', 'python', 'java', 'perl', 'ruby', 'php',
            'libwww', 'http', 'get', 'postman', 'insomnia',
            'apache', 'nginx', 'bot', 'crawler', 'spider', 'scraper'
        ],
        
        // التحقق من User-Agent
        checkUserAgent: function() {
            const userAgent = navigator.userAgent.toLowerCase();
            
            if (!userAgent) {
                return false; // User-Agent فارغ = بوت محتمل
            }
            
            for (let pattern of this.botPatterns) {
                if (userAgent.indexOf(pattern) !== -1) {
                    return false;
                }
            }
            
            return true;
        },
        
        // التحقق من قدرات المتصفح
        checkBrowserCapabilities: function() {
            // التحقق من وجود APIs مهمة
            const requiredAPIs = [
                'document', 'window', 'navigator', 'localStorage',
                'sessionStorage', 'XMLHttpRequest', 'fetch'
            ];
            
            for (let api of requiredAPIs) {
                if (typeof window[api] === 'undefined' && api !== 'fetch') {
                    return false;
                }
            }
            
            // التحقق من fetch
            if (typeof fetch === 'undefined') {
                return false;
            }
            
            // التحقق من localStorage
            try {
                localStorage.setItem('test', 'test');
                localStorage.removeItem('test');
            } catch (e) {
                return false;
            }
            
            return true;
        },
        
        // التحقق من أن الصفحة تم تحميلها بشكل صحيح
        checkPageLoad: function() {
            // التحقق من وجود DOM
            if (!document.body || !document.head) {
                return false;
            }
            
            // التحقق من أن JavaScript يعمل
            const testElement = document.createElement('div');
            testElement.style.display = 'none';
            document.body.appendChild(testElement);
            const isWorking = testElement.offsetParent === null;
            document.body.removeChild(testElement);
            
            return isWorking;
        },
        
        // إرسال إشارة للخادم أن المتصفح حقيقي
        sendBrowserVerification: async function() {
            try {
                // إنشاء token فريد
                const token = this.generateToken();
                sessionStorage.setItem('browser_token', token);
                
                // إرسال token للخادم للتحقق
                const response = await fetch('../apis/bot_verification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        token: token,
                        user_agent: navigator.userAgent,
                        screen_resolution: `${screen.width}x${screen.height}`,
                        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                        language: navigator.language,
                        platform: navigator.platform
                    })
                });
                
                return response.ok;
            } catch (e) {
                console.error('Verification failed:', e);
                return false;
            }
        },
        
        // توليد token فريد
        generateToken: function() {
            return Date.now().toString(36) + Math.random().toString(36).substr(2);
        },
        
        // منع البوتات
        blockBots: function() {
            if (!this.checkUserAgent() || 
                !this.checkBrowserCapabilities() || 
                !this.checkPageLoad()) {
                
                // تسجيل محاولة وصول بوت
                console.warn('Bot access attempt detected');
                
                // إخفاء المحتوى
                document.body.innerHTML = '';
                document.body.style.display = 'none';
                
                // إعادة توجيه أو إظهار رسالة خطأ
                window.location.href = '/403.html';
                
                return false;
            }
            
            return true;
        },
        
        // تهيئة الحماية
        init: async function() {
            // التحقق الفوري
            if (!this.blockBots()) {
                return;
            }
            
            // إرسال إشارة التحقق للخادم
            const verified = await this.sendBrowserVerification();
            if (!verified) {
                console.warn('Browser verification failed');
                // يمكن إضافة منطق إضافي هنا
            }
            
            // إضافة event listeners للتحقق المستمر
            this.setupContinuousCheck();
        },
        
        // إعداد فحص مستمر
        setupContinuousCheck: function() {
            // التحقق كل 30 ثانية
            setInterval(() => {
                if (!this.checkBrowserCapabilities()) {
                    window.location.href = '/403.html';
                }
            }, 30000);
            
            // التحقق عند تغيير focus
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    if (!this.checkBrowserCapabilities()) {
                        window.location.href = '/403.html';
                    }
                }
            });
        }
    };
    
    // تشغيل الحماية فور تحميل الصفحة
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            botDetection.init();
        });
    } else {
        botDetection.init();
    }
    
    // تصدير للاستخدام في ملفات أخرى
    window.botDetection = botDetection;
})();

