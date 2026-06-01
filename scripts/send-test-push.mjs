#!/usr/bin/env node
import crypto from 'node:crypto';

const [subscriptionJson, message = 'so glad you\'re here'] = process.argv.slice(2);

if (!subscriptionJson) {
	console.error('Usage: node scripts/send-test-push.mjs <subscription-json>');
	process.exit(1);
}

const vapid = {
	publicKey: process.env.BBB_PWA_VAPID_PUBLIC_KEY,
	privateD: process.env.BBB_PWA_VAPID_PRIVATE_D,
	publicX: process.env.BBB_PWA_VAPID_PUBLIC_X,
	publicY: process.env.BBB_PWA_VAPID_PUBLIC_Y,
	subject: process.env.BBB_PWA_VAPID_SUBJECT || 'mailto:bybookishbabe@gmail.com',
};

if (!vapid.publicKey || !vapid.privateD || !vapid.publicX || !vapid.publicY) {
	console.error('Missing BBB_PWA_VAPID_PUBLIC_KEY, BBB_PWA_VAPID_PRIVATE_D, BBB_PWA_VAPID_PUBLIC_X, or BBB_PWA_VAPID_PUBLIC_Y.');
	process.exit(1);
}

const subscription = JSON.parse(subscriptionJson);
const endpoint = new URL(subscription.endpoint);
const audience = `${endpoint.protocol}//${endpoint.host}`;

function base64url(input) {
	return Buffer.from(input)
		.toString('base64')
		.replace(/=/g, '')
		.replace(/\+/g, '-')
		.replace(/\//g, '_');
}

const header = base64url(JSON.stringify({ typ: 'JWT', alg: 'ES256' }));
const claims = base64url(JSON.stringify({
	aud: audience,
	exp: Math.floor(Date.now() / 1000) + 12 * 60 * 60,
	sub: vapid.subject,
}));
const signingInput = `${header}.${claims}`;
const privateKey = crypto.createPrivateKey({
	key: {
		kty: 'EC',
		crv: 'P-256',
		x: vapid.publicX,
		y: vapid.publicY,
		d: vapid.privateD,
	},
	format: 'jwk',
});
const signature = crypto.sign('sha256', Buffer.from(signingInput), { key: privateKey, dsaEncoding: 'ieee-p1363' });
const jwt = `${signingInput}.${base64url(signature)}`;
const payload = JSON.stringify({
	title: message,
	body: '',
	url: 'https://bybookishbabe.com/',
	icon: 'https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/pwa/bybookishbabe-icon-192.png?v=bybookishbabe-20260531-2',
});

function hkdfExtract(salt, ikm) {
	return crypto.createHmac('sha256', salt).update(ikm).digest();
}

function hkdfExpand(prk, info, length) {
	const blocks = [];
	let previous = Buffer.alloc(0);
	let counter = 1;

	while (Buffer.concat(blocks).length < length) {
		previous = crypto.createHmac('sha256', prk)
			.update(previous)
			.update(info)
			.update(Buffer.from([counter]))
			.digest();
		blocks.push(previous);
		counter += 1;
	}

	return Buffer.concat(blocks).subarray(0, length);
}

function publicKeyToRaw(key) {
	const jwk = key.export({ format: 'jwk' });
	return Buffer.concat([
		Buffer.from([4]),
		Buffer.from(jwk.x, 'base64url'),
		Buffer.from(jwk.y, 'base64url'),
	]);
}

function encryptPayload(subscription, text) {
	const userPublicKey = Buffer.from(subscription.keys.p256dh, 'base64url');
	const authSecret = Buffer.from(subscription.keys.auth, 'base64url');
	const local = crypto.createECDH('prime256v1');
	local.generateKeys();
	const serverPublicKey = local.getPublicKey();
	const sharedSecret = local.computeSecret(userPublicKey);
	const salt = crypto.randomBytes(16);
	const keyInfo = Buffer.concat([
		Buffer.from('WebPush: info\0'),
		userPublicKey,
		serverPublicKey,
	]);
	const prkKey = hkdfExtract(authSecret, sharedSecret);
	const ikm = hkdfExpand(prkKey, keyInfo, 32);
	const prk = hkdfExtract(salt, ikm);
	const cek = hkdfExpand(prk, Buffer.from('Content-Encoding: aes128gcm\0'), 16);
	const nonce = hkdfExpand(prk, Buffer.from('Content-Encoding: nonce\0'), 12);
	const plaintext = Buffer.concat([Buffer.from(text), Buffer.from([2])]);
	const cipher = crypto.createCipheriv('aes-128-gcm', cek, nonce);
	const ciphertext = Buffer.concat([cipher.update(plaintext), cipher.final(), cipher.getAuthTag()]);
	const recordSize = Buffer.alloc(4);
	recordSize.writeUInt32BE(4096, 0);

	return Buffer.concat([
		salt,
		recordSize,
		Buffer.from([serverPublicKey.length]),
		serverPublicKey,
		ciphertext,
	]);
}

const encryptedPayload = encryptPayload(subscription, payload);

const response = await fetch(subscription.endpoint, {
	method: 'POST',
	headers: {
		Authorization: `vapid t=${jwt}, k=${vapid.publicKey}`,
		TTL: '60',
		'Urgency': 'normal',
		'Content-Encoding': 'aes128gcm',
		'Content-Length': String(encryptedPayload.length),
	},
	body: encryptedPayload,
});

console.log(`${response.status} ${response.statusText}`);
const body = await response.text();
if (body) {
	console.log(body);
}

if (!response.ok && response.status !== 201) {
	process.exit(1);
}
