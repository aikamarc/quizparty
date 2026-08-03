<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Mesh, Program, Renderer, Triangle } from 'ogl';

const props = defineProps({
    raysOrigin: { type: String, default: 'top-center' },
    raysColor: { type: String, default: '#8b5cf6' },
    raysSpeed: { type: Number, default: 0.65 },
    lightSpread: { type: Number, default: 0.8 },
    rayLength: { type: Number, default: 2 },
    pulsating: { type: Boolean, default: true },
    fadeDistance: { type: Number, default: 1 },
    saturation: { type: Number, default: 0.8 },
    followMouse: { type: Boolean, default: true },
    mouseInfluence: { type: Number, default: 0.08 },
    noiseAmount: { type: Number, default: 0.03 },
    distortion: { type: Number, default: 0.02 },
});

const container = ref(null);
const visible = ref(false);
const mouse = ref({ x: 0.5, y: 0.5 });
const smoothMouse = ref({ x: 0.5, y: 0.5 });
let renderer;
let mesh;
let uniforms;
let frame;
let observer;
let resizeFrame;

const rgb = computed(() => {
    const match = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(props.raysColor);
    return match ? [1, 2, 3].map((index) => parseInt(match[index], 16) / 255) : [1, 1, 1];
});

const placement = (origin, width, height) => {
    const outside = 0.2;
    const positions = {
        'top-left': { anchor: [0, -outside * height], dir: [0, 1] },
        'top-right': { anchor: [width, -outside * height], dir: [0, 1] },
        left: { anchor: [-outside * width, 0.5 * height], dir: [1, 0] },
        right: { anchor: [(1 + outside) * width, 0.5 * height], dir: [-1, 0] },
        'bottom-left': { anchor: [0, (1 + outside) * height], dir: [0, -1] },
        'bottom-center': { anchor: [0.5 * width, (1 + outside) * height], dir: [0, -1] },
        'bottom-right': { anchor: [width, (1 + outside) * height], dir: [0, -1] },
    };
    return positions[origin] ?? { anchor: [0.5 * width, -outside * height], dir: [0, 1] };
};

const vertex = `attribute vec2 position; varying vec2 vUv; void main(){vUv=position*.5+.5;gl_Position=vec4(position,0.,1.);}`;
const fragment = `precision highp float;
uniform float iTime; uniform vec2 iResolution; uniform vec2 rayPos; uniform vec2 rayDir;
uniform vec3 raysColor; uniform float raysSpeed; uniform float lightSpread; uniform float rayLength;
uniform float pulsating; uniform float fadeDistance; uniform float saturation; uniform vec2 mousePos;
uniform float mouseInfluence; uniform float noiseAmount; uniform float distortion; varying vec2 vUv;
float noise(vec2 st){return fract(sin(dot(st.xy,vec2(12.9898,78.233)))*43758.5453123);}
float strength(vec2 source,vec2 reference,vec2 coord,float a,float b,float speed){
 vec2 delta=coord-source; vec2 norm=normalize(delta); float angle=dot(norm,reference);
 float warped=angle+distortion*sin(iTime*2.+length(delta)*.01)*.2;
 float spread=pow(max(warped,0.),1./max(lightSpread,.001)); float distance=length(delta);
 float maximum=iResolution.x*rayLength; float falloff=clamp((maximum-distance)/maximum,0.,1.);
 float fade=clamp((iResolution.x*fadeDistance-distance)/(iResolution.x*fadeDistance),.5,1.);
 float pulse=pulsating>.5?.8+.2*sin(iTime*speed*3.):1.;
 float base=clamp((.45+.15*sin(warped*a+iTime*speed))+(.3+.2*cos(-warped*b+iTime*speed)),0.,1.);
 return base*falloff*fade*spread*pulse;
}
void main(){vec2 coord=vec2(gl_FragCoord.x,iResolution.y-gl_FragCoord.y);vec2 direction=rayDir;
 if(mouseInfluence>0.){vec2 mouseDirection=normalize(mousePos*iResolution.xy-rayPos);direction=normalize(mix(rayDir,mouseDirection,mouseInfluence));}
 vec4 color=vec4(1.)*strength(rayPos,direction,coord,36.2214,21.11349,1.5*raysSpeed)*.5+vec4(1.)*strength(rayPos,direction,coord,22.3991,18.0234,1.1*raysSpeed)*.4;
 if(noiseAmount>0.){float n=noise(coord*.01+iTime*.1);color.rgb*=1.-noiseAmount+noiseAmount*n;}
 float brightness=1.-coord.y/iResolution.y;color.x*=.1+brightness*.8;color.y*=.3+brightness*.6;color.z*=.5+brightness*.5;
 float gray=dot(color.rgb,vec3(.299,.587,.114));color.rgb=mix(vec3(gray),color.rgb,saturation)*raysColor;gl_FragColor=color;}`;

const resize = () => {
    if (!container.value || !renderer || !uniforms) return;
    const width = container.value.clientWidth;
    const height = container.value.clientHeight;
    renderer.setSize(width, height);
    const dpr = renderer.dpr;
    uniforms.iResolution.value = [width * dpr, height * dpr];
    const { anchor, dir } = placement(props.raysOrigin, width * dpr, height * dpr);
    uniforms.rayPos.value = anchor;
    uniforms.rayDir.value = dir;
};

const render = (time) => {
    if (!visible.value || !renderer || !mesh) return;
    uniforms.iTime.value = time * 0.001;
    smoothMouse.value.x = smoothMouse.value.x * 0.92 + mouse.value.x * 0.08;
    smoothMouse.value.y = smoothMouse.value.y * 0.92 + mouse.value.y * 0.08;
    uniforms.mousePos.value = [smoothMouse.value.x, smoothMouse.value.y];
    renderer.render({ scene: mesh });
    frame = requestAnimationFrame(render);
};

const initialize = async () => {
    if (!container.value || renderer) return;
    await nextTick();
    renderer = new Renderer({ dpr: Math.min(window.devicePixelRatio || 1, 2), alpha: true, antialias: false, powerPreference: 'high-performance' });
    renderer.gl.canvas.style.width = '100%';
    renderer.gl.canvas.style.height = '100%';
    container.value.appendChild(renderer.gl.canvas);
    uniforms = {
        iTime: { value: 0 }, iResolution: { value: [1, 1] }, rayPos: { value: [0, 0] }, rayDir: { value: [0, 1] },
        raysColor: { value: rgb.value }, raysSpeed: { value: props.raysSpeed }, lightSpread: { value: props.lightSpread },
        rayLength: { value: props.rayLength }, pulsating: { value: props.pulsating ? 1 : 0 }, fadeDistance: { value: props.fadeDistance },
        saturation: { value: props.saturation }, mousePos: { value: [0.5, 0.5] }, mouseInfluence: { value: props.mouseInfluence },
        noiseAmount: { value: props.noiseAmount }, distortion: { value: props.distortion },
    };
    mesh = new Mesh(renderer.gl, { geometry: new Triangle(renderer.gl), program: new Program(renderer.gl, { vertex, fragment, uniforms }) });
    resize();
    frame = requestAnimationFrame(render);
};

const handleMouse = (event) => {
    if (!props.followMouse || !container.value) return;
    const rect = container.value.getBoundingClientRect();
    mouse.value = { x: (event.clientX - rect.left) / rect.width, y: (event.clientY - rect.top) / rect.height };
};
const handleResize = () => { cancelAnimationFrame(resizeFrame); resizeFrame = requestAnimationFrame(resize); };

onMounted(() => {
    observer = new IntersectionObserver(([entry]) => { visible.value = entry.isIntersecting; }, { rootMargin: '50px' });
    observer.observe(container.value);
    window.addEventListener('resize', handleResize, { passive: true });
    window.addEventListener('mousemove', handleMouse, { passive: true });
});

watch(visible, (value) => { if (value) { initialize(); if (renderer && !frame) frame = requestAnimationFrame(render); } else { cancelAnimationFrame(frame); frame = null; } });

onUnmounted(() => {
    observer?.disconnect(); cancelAnimationFrame(frame); cancelAnimationFrame(resizeFrame);
    window.removeEventListener('resize', handleResize); window.removeEventListener('mousemove', handleMouse);
    renderer?.gl.getExtension('WEBGL_lose_context')?.loseContext();
    renderer?.gl.canvas.remove(); renderer = null; mesh = null; uniforms = null;
});
</script>

<template><div ref="container" class="pointer-events-none h-full w-full overflow-hidden" /></template>
