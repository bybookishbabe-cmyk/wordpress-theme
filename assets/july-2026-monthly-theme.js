(function () {
	'use strict';

	var root = document.querySelector('.bbb-midnight-kit');
	if (!root || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		return;
	}

	function fitCanvas(canvas) {
		var rect = canvas.getBoundingClientRect();
		var scale = Math.min(window.devicePixelRatio || 1, 2);
		canvas.width = Math.max(1, Math.floor(rect.width * scale));
		canvas.height = Math.max(1, Math.floor(rect.height * scale));
		var context = canvas.getContext('2d');
		context.setTransform(scale, 0, 0, scale, 0, 0);
		return { ctx: context, width: rect.width, height: rect.height };
	}

	function starfield() {
		var canvas = root.querySelector('[data-midnight-starfield]');
		if (!canvas) {
			return;
		}
		var ctx;
		var width = 0;
		var height = 0;
		var stars = [];
		var sparkles = [];
		var shooters = [];

		function rebuild() {
			var fitted = fitCanvas(canvas);
			ctx = fitted.ctx;
			width = fitted.width;
			height = Math.max(root.scrollHeight, window.innerHeight);
			canvas.style.height = height + 'px';
			fitted = fitCanvas(canvas);
			ctx = fitted.ctx;
			width = fitted.width;
			height = fitted.height;
			stars = Array.from({ length: 280 }, function () {
				return {
					x: Math.random() * width,
					y: Math.random() * height,
					r: Math.random() * 1.2 + 0.2,
					base: Math.random() * 0.5 + 0.15,
					speed: 0.003 + Math.random() * 0.008,
					offset: Math.random() * Math.PI * 2,
					tint: Math.random() < 0.18 ? [192, 160, 204] : Math.random() < 0.28 ? [174, 200, 216] : [220, 230, 240]
				};
			});
			sparkles = Array.from({ length: 38 }, function () {
				return {
					x: Math.random() * width,
					y: Math.random() * height,
					size: Math.random() * 3 + 1.5,
					base: Math.random() * 0.6 + 0.2,
					phase: Math.random() * Math.PI * 2,
					speed: 0.015 + Math.random() * 0.025,
					tint: Math.random() < 0.45 ? [192, 160, 204] : [174, 200, 216]
				};
			});
		}

		function shoot() {
			shooters.push({
				x: Math.random() * width * 0.8 + width * 0.1,
				y: Math.random() * height * 0.42,
				length: 80 + Math.random() * 120,
				angle: Math.PI / 4 + (Math.random() - 0.5) * 0.4,
				speed: 5 + Math.random() * 6,
				alpha: 0.9,
				progress: 0,
				tint: Math.random() < 0.4 ? [192, 160, 204] : [174, 200, 216]
			});
		}

		var time = 0;
		function drawSparkle(x, y, size, alpha, tint) {
			ctx.save();
			ctx.translate(x, y);
			ctx.globalAlpha = alpha;
			ctx.strokeStyle = 'rgb(' + tint.join(',') + ')';
			ctx.lineWidth = 0.8;
			ctx.beginPath();
			ctx.moveTo(0, -size);
			ctx.lineTo(0, size);
			ctx.stroke();
			ctx.beginPath();
			ctx.moveTo(-size, 0);
			ctx.lineTo(size, 0);
			ctx.stroke();
			ctx.lineWidth = 0.5;
			ctx.beginPath();
			ctx.moveTo(-size * 0.55, -size * 0.55);
			ctx.lineTo(size * 0.55, size * 0.55);
			ctx.stroke();
			ctx.beginPath();
			ctx.moveTo(size * 0.55, -size * 0.55);
			ctx.lineTo(-size * 0.55, size * 0.55);
			ctx.stroke();
			ctx.restore();
		}

		function draw() {
			ctx.clearRect(0, 0, width, height);
			time += 0.016;
			stars.forEach(function (star) {
				var alpha = star.base * (0.5 + 0.5 * Math.sin(time * star.speed * 60 + star.offset));
				ctx.beginPath();
				ctx.arc(star.x, star.y, star.r, 0, Math.PI * 2);
				ctx.fillStyle = 'rgba(' + star.tint.join(',') + ',' + alpha + ')';
				ctx.fill();
			});
			sparkles.forEach(function (sparkle) {
				var alpha = sparkle.base * (0.4 + 0.6 * Math.abs(Math.sin(time * sparkle.speed * 60 + sparkle.phase)));
				drawSparkle(sparkle.x, sparkle.y, sparkle.size, alpha, sparkle.tint);
			});
			shooters = shooters.filter(function (shooter) {
				return shooter.alpha > 0.01;
			});
			shooters.forEach(function (shooter) {
				shooter.progress += shooter.speed;
				shooter.alpha *= 0.97;
				var dx = Math.cos(shooter.angle) * shooter.progress;
				var dy = Math.sin(shooter.angle) * shooter.progress;
				var tailX = Math.cos(shooter.angle) * Math.max(0, shooter.progress - shooter.length);
				var tailY = Math.sin(shooter.angle) * Math.max(0, shooter.progress - shooter.length);
				var gradient = ctx.createLinearGradient(shooter.x + tailX, shooter.y + tailY, shooter.x + dx, shooter.y + dy);
				gradient.addColorStop(0, 'rgba(' + shooter.tint.join(',') + ',0)');
				gradient.addColorStop(1, 'rgba(' + shooter.tint.join(',') + ',' + shooter.alpha + ')');
				ctx.beginPath();
				ctx.moveTo(shooter.x + tailX, shooter.y + tailY);
				ctx.lineTo(shooter.x + dx, shooter.y + dy);
				ctx.strokeStyle = gradient;
				ctx.lineWidth = 1.2;
				ctx.stroke();
			});
			window.requestAnimationFrame(draw);
		}

		rebuild();
		window.addEventListener('resize', rebuild);
		window.setInterval(shoot, 5200);
		draw();
	}

	function waves() {
		var canvas = root.querySelector('[data-midnight-waves]');
		if (!canvas) {
			return;
		}
		var ctx;
		var width = 0;
		var height = 0;
		var time = 0;
		var layers = [
			{ amp: 14, freq: 0.011, speed: 0.4, y: 0.38, color: 'rgba(174,200,216,0.18)' },
			{ amp: 20, freq: 0.008, speed: 0.28, y: 0.52, color: 'rgba(154,120,172,0.18)' },
			{ amp: 10, freq: 0.016, speed: 0.55, y: 0.48, color: 'rgba(174,200,216,0.1)' },
			{ amp: 28, freq: 0.006, speed: 0.18, y: 0.68, color: 'rgba(26,53,84,0.6)' },
			{ amp: 18, freq: 0.009, speed: 0.32, y: 0.78, color: 'rgba(13,30,56,0.75)' }
		];

		function resize() {
			var fitted = fitCanvas(canvas);
			ctx = fitted.ctx;
			width = fitted.width;
			height = fitted.height;
		}

		function draw() {
			ctx.clearRect(0, 0, width, height);
			layers.forEach(function (layer) {
				ctx.beginPath();
				for (var x = 0; x <= width; x += 2) {
					var y = height * layer.y + Math.sin(x * layer.freq + time * layer.speed) * layer.amp + Math.sin(x * layer.freq * 1.7 + time * layer.speed * 0.6) * (layer.amp * 0.4);
					if (x === 0) {
						ctx.moveTo(x, y);
					} else {
						ctx.lineTo(x, y);
					}
				}
				ctx.lineTo(width, height);
				ctx.lineTo(0, height);
				ctx.closePath();
				ctx.fillStyle = layer.color;
				ctx.fill();
			});
			time += 0.018;
			window.requestAnimationFrame(draw);
		}

		resize();
		window.addEventListener('resize', resize);
		draw();
	}

	function sparkleCanvases(selector, paletteSet) {
		root.querySelectorAll(selector).forEach(function (canvas, index) {
			var ctx;
			var width = 0;
			var height = 0;
			var palette = paletteSet[index % paletteSet.length];
			var points = [];
			var time = 0;

			function resize() {
				var fitted = fitCanvas(canvas);
				ctx = fitted.ctx;
				width = fitted.width;
				height = fitted.height;
				points = Array.from({ length: selector.indexOf('phone') > -1 ? 30 : 18 }, function () {
					return {
						x: Math.random(),
						y: Math.random(),
						size: Math.random() * 2 + 0.7,
						phase: Math.random() * Math.PI * 2,
						speed: 0.01 + Math.random() * 0.02,
						tint: palette[Math.floor(Math.random() * palette.length)],
						dot: Math.random() > 0.45
					};
				});
			}

			function drawCross(x, y, size, tint, alpha) {
				ctx.save();
				ctx.translate(x, y);
				ctx.globalAlpha = alpha;
				ctx.strokeStyle = 'rgb(' + tint.join(',') + ')';
				ctx.lineWidth = 0.7;
				ctx.beginPath();
				ctx.moveTo(0, -size);
				ctx.lineTo(0, size);
				ctx.stroke();
				ctx.beginPath();
				ctx.moveTo(-size, 0);
				ctx.lineTo(size, 0);
				ctx.stroke();
				ctx.restore();
			}

			function draw() {
				ctx.clearRect(0, 0, width, height);
				points.forEach(function (point) {
					var alpha = 0.1 + 0.65 * Math.abs(Math.sin(time * point.speed * 60 + point.phase));
					var x = point.x * width;
					var y = point.y * height;
					if (point.dot) {
						ctx.beginPath();
						ctx.arc(x, y, point.size * 0.45, 0, Math.PI * 2);
						ctx.fillStyle = 'rgba(' + point.tint.join(',') + ',' + alpha + ')';
						ctx.fill();
					} else {
						drawCross(x, y, point.size, point.tint, alpha);
					}
				});
				time += 1;
				window.requestAnimationFrame(draw);
			}

			resize();
			window.addEventListener('resize', resize);
			draw();
		});
	}

	function sectionReveals() {
		var revealItems = Array.from(root.querySelectorAll('.bbb-midnight-kit__hero, .bbb-midnight-kit__nav, .bbb-midnight-section, .bbb-midnight-kit__footer'));
		if (!revealItems.length) {
			return;
		}

		root.classList.add('is-motion-ready');
		revealItems.forEach(function (item, index) {
			var name = item.id || (item.classList.contains('bbb-midnight-kit__hero') ? 'hero' : item.classList.contains('bbb-midnight-kit__nav') ? 'nav' : 'footer');
			item.setAttribute('data-midnight-reveal', name);
			item.style.setProperty('--reveal-index', index);
		});

		if (!('IntersectionObserver' in window)) {
			revealItems.forEach(function (item) {
				item.classList.add('is-visible');
			});
			return;
		}

		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (!entry.isIntersecting) {
						return;
					}
					entry.target.classList.add('is-visible');
					observer.unobserve(entry.target);
				});
			},
			{ rootMargin: '0px 0px -12% 0px', threshold: 0.14 }
		);

		revealItems.forEach(function (item) {
			observer.observe(item);
		});
	}

	sectionReveals();
	starfield();
	waves();
	sparkleCanvases('[data-midnight-sparkles]', [
		[[174, 200, 216], [200, 220, 235]],
		[[154, 120, 172], [192, 160, 204]],
		[[200, 170, 132], [220, 195, 155]],
		[[192, 160, 204], [174, 200, 216]]
	]);
	sparkleCanvases('[data-midnight-phone-sparkles]', [
		[[174, 200, 216], [220, 235, 245]],
		[[192, 160, 204], [154, 120, 172]],
		[[174, 200, 216], [140, 185, 210]],
		[[200, 170, 132], [220, 195, 155]]
	]);
}());
