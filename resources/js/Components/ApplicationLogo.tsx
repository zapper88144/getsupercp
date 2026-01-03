import { SVGAttributes } from 'react';

export default function ApplicationLogo(props: SVGAttributes<SVGElement>) {
    return (
        <div className={`flex items-center justify-center rounded-xl bg-indigo-600 ${props.className}`}>
            <span className="text-2xl font-bold text-white">S</span>
        </div>
    );
}

